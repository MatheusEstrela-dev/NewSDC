<?php

declare(strict_types=1);

namespace App\Modules\Geoespacial\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Geoespacial\Repositories\GeoCamadaRepository;
use App\Modules\Geoespacial\Requests\SubirCamadaRequest;
use App\Modules\Geoespacial\Services\KmlExtrator;
use App\Modules\Medalhao\Jobs\NormalizarSilverJob;
use App\Modules\Medalhao\Models\IngestaoBruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GeoUploadController extends Controller
{
    public function __construct(
        private readonly GeoCamadaRepository $repository,
        private readonly KmlExtrator $extrator,
    ) {
    }

    public function index(Request $request): Response
    {
        $camadaId = $request->integer('camada') ?: null;

        return Inertia::render('Geoespacial/Camadas', [
            'camadas' => $this->repository->camadas()->all(),
            'feicoes' => $this->repository->mapa($camadaId)->all(),
            'cruzamento' => $camadaId !== null ? $this->repository->cruzamento($camadaId) : null,
            'camadaSelecionada' => $camadaId,
            'dominios' => config('geoespacial.dominios'),
            'bbox' => config('medalhao.inmet.bbox'),
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

        $envelope = json_encode([
            'dominio' => $request->string('dominio')->toString(),
            'nome' => $request->string('nome')->toString(),
            'arquivo_nome' => $arquivo->getClientOriginalName(),
            'emitido_em' => $request->date('emitido_em')?->toDateString(),
            'valido_ate' => $request->date('valido_ate')?->toDateString(),
            'nivel' => $request->string('nivel')->toString(),
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

        return back()->with('sucesso', 'Camada enviada. O processamento acontece em segundo plano.');
    }
}
