<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Geoespacial\DTOs\MetadadoCamadaDTO;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Requests\AtualizarCamadaRequest;
use App\Modules\Geoespacial\Services\CicloDeVidaDaCamada;
use App\Modules\Geoespacial\Services\ProcedenciaDoEnvio;
use App\Modules\Geoespacial\Support\AcessoACamada;
use App\Modules\Shared\Geo\CaixaEnvolvente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A vida de UMA camada: abrir, editar metadado, arquivar, reativar, baixar o
 * original e reprocessar a geometria.
 *
 * Separado de GeoUploadController de proposito. Aquele trata do que ENTRA no
 * sistema -- upload, tela de envio e fila de revisao -- e ja tem seis acoes.
 * Aqui e o que acontece com a camada depois de ela existir. Juntar os dois
 * daria um controller de treze metodos com dois assuntos.
 */
class GeoCamadaController extends Controller
{
    public function __construct(
        private readonly GeoCamadaRepository $repository,
        private readonly CicloDeVidaDaCamada $ciclo,
        private readonly ProcedenciaDoEnvio $procedencia,
        private readonly AcessoACamada $acesso,
    ) {
    }

    /**
     * Detalhe da camada.
     *
     * Le TODO do Silver -- feicoes, geometria e municipios atingidos -- e nao do
     * Gold. O Gold so tem camada aprovada, e esta tela precisa abrir pendente,
     * recusada e arquivada: e justamente na pendente que o revisor quer ver o
     * alcance antes de decidir.
     */
    public function show(Request $request, int $camada): Response
    {
        $dados = $this->repository->camada($camada);

        abort_if($dados === null, 404);

        $municipioDoUsuario = $this->municipioDoUsuario($request);

        // Esconder na listagem nao e autorizacao: sem esta linha, /geoespacial/7
        // digitado na barra de endereco abriria a pendente do municipio vizinho,
        // com motivo de recusa e tudo.
        abort_unless($this->acesso->podeVer($request->user(), $dados, $municipioDoUsuario), 403);

        $municipios = $this->repository->municipiosDaCamada($camada);
        $cruzamento = $this->repository->cruzamento($camada);

        /*
         * A contagem de municipios vem da consulta ao Silver, sobrescrevendo a
         * do cruzamento.
         *
         * cruzamento() le gold.geo_camada_municipios, que so contem camada
         * aprovada -- numa camada pendente ele devolveria 0 enquanto a lista
         * ao lado, lida do Silver, mostraria doze nomes. Dois numeros
         * discordando na mesma tela e pior que um numero so.
         */
        $cruzamento['municipios'] = $municipios->count();

        return Inertia::render('Geoespacial/Detalhe', [
            'camada' => $dados,
            'feicoes' => $this->repository->feicoesDaCamada($camada)->all(),
            'municipios' => $municipios->all(),
            'cruzamento' => $cruzamento,
            'acoes' => $this->acesso->acoes($request->user(), $dados, $municipioDoUsuario),
            'dominios' => config('geoespacial.dominios'),
            'bbox' => CaixaEnvolvente::deConfig(config('medalhao.inmet.bbox'))->paraArray(),
        ]);
    }

    /** Edita metadado. A geometria nao passa por aqui -- ver MetadadoCamadaDTO. */
    public function update(AtualizarCamadaRequest $request, int $camada): RedirectResponse
    {
        // A autorizacao de instancia (ownership + status) esta no authorize() do
        // Request, e nao aqui: assim ela roda antes da validacao e o controller
        // fica com uma responsabilidade.
        try {
            $this->ciclo->editar($camada, MetadadoCamadaDTO::fromRequest($request->validated()));
        } catch (RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', 'Camada atualizada.');
    }

    public function arquivar(Request $request, int $camada): RedirectResponse
    {
        abort_unless($request->user()?->can('geoespacial.camadas.arquivar'), 403);

        $dados = $request->validate([
            // Opcional, diferente do motivo de recusa: recusa e resposta a um
            // municipio que espera retorno, e arquivamento e decisao interna da
            // CEDEC sobre uma camada que ja estava publicada.
            'motivo' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->ciclo->arquivar($camada, (int) $request->user()->id, $dados['motivo'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', 'Camada arquivada e retirada do mapa.');
    }

    public function reativar(Request $request, int $camada): RedirectResponse
    {
        abort_unless($request->user()?->can('geoespacial.camadas.arquivar'), 403);

        try {
            $this->ciclo->reativar($camada, (int) $request->user()->id);
        } catch (RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', 'Camada reativada e publicada no mapa.');
    }

    public function reprocessar(Request $request, int $camada): RedirectResponse
    {
        abort_unless($request->user()?->can('geoespacial.camadas.revisar'), 403);

        try {
            $total = $this->ciclo->reprocessar($camada);
        } catch (RuntimeException $e) {
            return back()->withErrors(['camada' => $e->getMessage()]);
        }

        return back()->with('sucesso', "Geometria reprocessada: {$total} feicoes regravadas.");
    }

    /**
     * Baixa o KML/KMZ como o municipio enviou.
     *
     * O arquivo estava sendo guardado no disco geo_municipal desde o primeiro
     * envio e NAO tinha rota nenhuma que o lesse -- o artefato auditavel do
     * modulo era inalcancavel pela interface.
     */
    public function baixar(Request $request, int $camada): StreamedResponse
    {
        $dados = $this->repository->camada($camada);

        abort_if($dados === null, 404);

        $municipioDoUsuario = $this->municipioDoUsuario($request);

        abort_unless(
            $this->acesso->acoes($request->user(), $dados, $municipioDoUsuario)['baixar'],
            403
        );

        $disco = Storage::disk('geo_municipal');

        // O caminho vem do banco, mas o arquivo pode nao estar la: bind mount
        // trocado, restauracao de banco sem os arquivos, limpeza manual. 404 com
        // o nome do arquivo diz o que faltou; deixar o Storage estourar daria
        // 500 sem explicacao.
        abort_unless($disco->exists($dados->arquivo_caminho), 404, "Arquivo {$dados->arquivo_nome} nao esta no disco.");

        // Baixa com o NOME ORIGINAL, e nao com o hash do disco: o hash e o nome
        // interno para evitar travessia de diretorio e colisao, mas quem baixa
        // quer o arquivo que a prefeitura mandou.
        return $disco->download($dados->arquivo_caminho, $dados->arquivo_nome);
    }

    /**
     * Municipio do usuario, ou null para quem nao e municipal.
     *
     * Resolvido UMA vez por request e passado adiante: ProcedenciaDoEnvio
     * consulta compdec_orgao_user e compdec_orgaos, e chamar isso por linha de
     * listagem seria N+1 sobre duas tabelas.
     */
    private function municipioDoUsuario(Request $request): ?int
    {
        $usuario = $request->user();

        return $usuario === null ? null : $this->procedencia->para($usuario)->municipioId;
    }
}
