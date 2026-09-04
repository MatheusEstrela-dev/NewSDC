<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Requests\SubirCamadaRequest;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Geoespacial\Services\ProcedenciaDoEnvio;
use App\Modules\Geoespacial\Services\RevisaoDeCamadas;
use App\Modules\Medalhao\Jobs\NormalizarSilverJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use App\Modules\Shared\Geo\CaixaEnvolvente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeoUploadController extends Controller
{
    public function __construct(
        private readonly GeoCamadaRepository $repository,
        private readonly KmlExtrator $extrator,
        private readonly ProcedenciaDoEnvio $procedencia,
        private readonly RevisaoDeCamadas $revisao,
    ) {
    }

    public function index(Request $request): Response
    {
        $camadaId = $request->integer('camada') ?: null;

        // Quem revisa ve tudo; o municipio ve as proprias e as aprovadas. Sem
        // este recorte, o municipio A lia as pendentes e recusadas do B,
        // inclusive o texto da recusa.
        $veTudo = $request->user()?->can('geoespacial.camadas.revisar') ?? false;
        $procedencia = $veTudo ? null : $this->procedencia->para($request->user());

        return Inertia::render('Geoespacial/Camadas', [
            'camadas' => $this->repository->camadas(
                municipioId: $procedencia?->municipioId,
                veTudo: $veTudo,
            )->all(),
            'feicoes' => $this->repository->mapa($camadaId)->all(),
            'cruzamento' => $camadaId !== null ? $this->repository->cruzamento($camadaId) : null,
            'camadaSelecionada' => $camadaId,
            'dominios' => config('geoespacial.dominios'),
            'bbox' => CaixaEnvolvente::deConfig(config('medalhao.inmet.bbox'))->paraArray(),
        ]);
    }

    /**
     * O request faz tres coisas e nenhuma delas e parse: valida, grava o cru no
     * Bronze, despacha job. O ZIP so e aberto no worker da fila -- e por isso
     * que o Octane nao sente o upload.
     *
     * A unica leitura aqui e conteudoDeArquivo(), que para KMZ abre o ZIP. E o
     * minimo necessario para guardar o KML e nao o container, e ja carrega as
     * guardas de tamanho.
     */
    public function upload(SubirCamadaRequest $request): RedirectResponse
    {
        $arquivo = $request->file('arquivo');
        $kml = $this->extrator->conteudoDeArquivo($arquivo->getRealPath());

        // O dedup real acontece no Silver, por hash da GEOMETRIA -- trocar o
        // nome ou o nivel na tela nao deve duplicar a mesma area no mapa.
        //
        // Sem esta checagem aqui, porem, o upload respondia "Camada enviada" e
        // sumia: o job rodava, o ON CONFLICT recusava, e o operador ficava
        // olhando uma lista que nao mudava, sem saber por que. Antecipar a
        // resposta custa um hash e uma leitura por indice unico.
        $jaExiste = $this->repository->camadaDoHash(hash('sha256', $kml));

        if ($jaExiste !== null) {
            return back()->withErrors([
                'arquivo' => "Esta geometria ja foi importada como \"{$jaExiste->nome}\""
                    . ($jaExiste->emitido_em !== null ? " (emitida em {$jaExiste->emitido_em})" : '')
                    . '. O sistema compara o conteudo do arquivo, nao o nome: para trazer areas'
                    . ' diferentes, envie um KML diferente.',
            ]);
        }

        // A ORIGEM vem da capacidade do usuario, nunca do formulario. Quem
        // revisa (CEDEC) publica direto; quem so envia manda para aprovacao.
        // Um campo 'origem' no HTML permitiria o municipio se declarar
        // estadual e pular a moderacao.
        $publicaDireto = $request->user()?->can('geoespacial.camadas.revisar') ?? false;

        $procedencia = null;
        $caminhoArquivo = null;

        if (! $publicaDireto) {
            $procedencia = $this->procedencia->para($request->user());

            if (! $procedencia->permitido) {
                return back()->withErrors(['arquivo' => $procedencia->motivo]);
            }

            // Guarda o arquivo COMO O MUNICIPIO ENVIOU, inclusive KMZ
            // compactado. O Bronze guarda o KML ja extraido do ZIP, entao sem
            // isto nao ha como provar depois o que o municipio mandou.
            $caminhoArquivo = $this->guardarOriginal($arquivo, $procedencia->municipioId());
        }

        $envelope = json_encode([
            'dominio' => $request->string('dominio')->toString(),
            'nome' => $request->string('nome')->toString(),
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'emitido_em' => $request->date('emitido_em')?->toDateString(),
            'valido_ate' => $request->date('valido_ate')?->toDateString(),
            'nivel' => $request->string('nivel')->toString(),
            'origem' => $publicaDireto ? 'estadual' : 'municipal',
            'status' => $publicaDireto ? 'aprovada' : 'pendente',
            'municipio_id' => $procedencia?->municipioId,
            'orgao_id' => $procedencia?->orgaoId,
            'enviado_por' => $request->user()?->id,
            'arquivo_caminho' => $caminhoArquivo,
            'kml' => $kml,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $bronze = IngestaoBruta::create([
            'fonte' => 'geo-upload',
            'conteudo_bruto' => $envelope,
            'formato' => 'geo-kml',
            'hash_conteudo' => hash('sha256', $envelope),
            'meta' => [
                'arquivo_nome' => $arquivo->getClientOriginalName(),
                'bytes' => $arquivo->getSize(),
                'usuario_id' => $request->user()?->id,
            ],
            'coletado_em' => now(),
            'verificado_em' => now(),
        ]);

        NormalizarSilverJob::dispatch((int) $bronze->id, 'geo-upload');


        // A notificacao aos revisores NAO sai daqui. NormalizarSilverJob e
        // assincrono: neste ponto a camada ainda nao existe no Silver, e a
        // consulta pelo ingestao_id voltava vazia. Quem avisa e o repositorio,
        // que e onde a camada de fato nasce -- ver GeoCamadaRepository.

        return back()->with('sucesso', $publicaDireto
            ? 'Camada enviada. O processamento acontece em segundo plano.'
            : 'Camada enviada para aprovacao da CEDEC. Ela aparece no mapa depois de aprovada.');
    }

    /** Fila de revisao da CEDEC. */
    public function revisao(Request $request): Response
    {
        abort_unless($request->user()?->can('geoespacial.camadas.revisar'), 403);

        return Inertia::render('Geoespacial/Revisao', [
            'pendentes' => $this->revisao->pendentes()->all(),
            'dominios' => config('geoespacial.dominios'),
            'bbox' => CaixaEnvolvente::deConfig(config('medalhao.inmet.bbox'))->paraArray(),
        ]);
    }

    public function aprovar(Request $request, int $camada): RedirectResponse
    {
        abort_unless($request->user()?->can('geoespacial.camadas.revisar'), 403);

        try {
            $this->revisao->aprovar($camada, (int) $request->user()->id);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', 'Camada aprovada e publicada no mapa.');
    }

    public function recusar(Request $request, int $camada): RedirectResponse
    {
        abort_unless($request->user()?->can('geoespacial.camadas.revisar'), 403);

        $dados = $request->validate([
            // Obrigatorio por decisao de produto: recusa sem justificativa
            // deixa o municipio sem saber o que corrigir, e ele reenvia o
            // mesmo arquivo.
            'motivo' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        try {
            $this->revisao->recusar($camada, (int) $request->user()->id, $dados['motivo']);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', 'Camada recusada e o municipio foi avisado.');
    }

    /**
     * Grava o arquivo original no disco geo_municipal.
     *
     * Layout espelhando o particionamento do Bronze:
     * municipio=<ibge>/<ano>/<hash12>.<ext>. Particionar por municipio e ano
     * mantem o diretorio navegavel a olho no bind mount -- 893 municipios num
     * diretorio plano seriam ilegiveis.
     *
     * O nome vem do hash, e nao do nome enviado: nome de arquivo de terceiro
     * chega com acento, espaco, barra e caractere de controle, e vira
     * travessia de diretorio se usado cru. A extensao e recuperada da
     * assinatura, nao do que o usuario escreveu.
     */
    private function guardarOriginal(\Illuminate\Http\UploadedFile $arquivo, int $municipioId): string
    {
        $ibge = DB::table('municipios')->where('id', $municipioId)->value('codigo_ibge') ?? $municipioId;

        $conteudo = (string) file_get_contents($arquivo->getRealPath());
        $extensao = str_starts_with($conteudo, 'PK') ? 'kmz' : 'kml';

        $caminho = sprintf(
            'municipio=%s/%s/%s.%s',
            $ibge,
            now()->format('Y'),
            substr(hash('sha256', $conteudo), 0, 12),
            $extensao
        );

        Storage::disk('geo_municipal')->put($caminho, $conteudo);

        return $caminho;
    }
}
