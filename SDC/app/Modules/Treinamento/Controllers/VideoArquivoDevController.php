<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Treinamento\Services\Video\AutorizacaoVideoService;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * So desenvolvimento local: em producao quem entrega os arquivos HLS e o
 * Caddy (apos o forward_auth), nunca o app.
 */
class VideoArquivoDevController extends Controller
{
    public function __construct(private readonly AutorizacaoVideoService $autorizacao) {}

    public function __invoke(int $videoId, string $versao, string $arquivo): BinaryFileResponse|Response
    {
        abort_if(app()->isProduction() || ! config('treinamento.video.servir_pelo_app'), 404);

        if (! $this->autorizacao->autorizarUri("/videos/{$videoId}/{$versao}/{$arquivo}")) {
            return response('', 403)->header('Cache-Control', 'no-store');
        }

        $disco = Storage::disk(config('treinamento.video.disco'));
        $caminho = CaminhosVideo::hlsVersao($videoId, $versao).'/'.$arquivo;
        abort_unless($disco->exists($caminho), 404);

        return response()->file($disco->path($caminho), [
            'Content-Type' => str_ends_with($arquivo, '.m3u8') ? 'application/vnd.apple.mpegurl' : 'video/MP2T',
        ]);
    }
}
