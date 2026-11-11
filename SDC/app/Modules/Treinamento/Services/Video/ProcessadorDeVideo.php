<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use App\Modules\Treinamento\Enums\VideoStatus;
use App\Modules\Treinamento\Exceptions\FalhaDeVideo;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Support\CaminhosVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Original enviado -> HLS publicado por versao. Reexecutavel: o original ja
 * montado e reaproveitado e os temporarios de uma tentativa anterior sao
 * descartados. Durante o processamento a hls_versao antiga fica intacta.
 */
class ProcessadorDeVideo
{
    public function __construct(
        private readonly MontadorDeOriginal $montador,
        private readonly Ffmpeg $ffmpeg,
        private readonly PublicadorHls $publicador,
        private readonly VideoMetrics $metricas,
    ) {}

    public function processar(Video $video, VideoUpload $upload): void
    {
        $inicio = microtime(true);
        $disco = Storage::disk('treinamentos');
        $originalAnterior = $video->original_path;
        $versaoAnterior = $video->hls_versao;

        $video->update(['status' => VideoStatus::PROCESSANDO]);

        try {
            $this->publicador->removerTemporarios($video);

            $original = $this->etapa($video, 'montagem', fn () => $this->montador->montar($upload));
            $sondagem = $this->etapa($video, 'sondagem', fn () => $this->ffmpeg->sondar($disco->path($original)));

            $temporario = CaminhosVideo::hlsTemporario($video->id, (string) Str::uuid());
            $this->etapa($video, 'transcodificacao', fn () => $this->ffmpeg->transcodificar(
                $disco->path($original),
                $disco->path($temporario),
                $sondagem,
                CaminhosVideo::logFfmpeg($video->id, now()->format('YmdHis')),
            ));

            $versao = $this->etapa($video, 'publicacao', fn () => $this->publicador->publicar($video, $temporario));
        } catch (FalhaDeVideo $e) {
            $this->publicador->removerTemporarios($video);
            $this->registrarFalha($video, $e->getMessage());
            $this->metricas->registrarProcessamento(false, (int) round(microtime(true) - $inicio), 0);

            // A decisao de tentar de novo (definitiva ou nao) e do job.
            throw $e;
        }

        $manterOriginal = (bool) config('treinamento.video.manter_original');

        $video->update([
            'status' => VideoStatus::PRONTO,
            'hls_versao' => $versao,
            'original_path' => $manterOriginal ? $original : null,
            'duracao_segundos' => $sondagem->duracaoSegundos,
            'largura' => $sondagem->largura,
            'altura' => $sondagem->altura,
            'tamanho_bytes' => $upload->tamanho_total,
            'processado_em' => now(),
            'erro' => null,
            'tentativas' => 0,
        ]);

        if ($versaoAnterior !== null) {
            $this->publicador->removerVersao($video, $versaoAnterior);
        }
        if (! $manterOriginal) {
            $disco->delete($original);
        }
        if ($originalAnterior !== null && $originalAnterior !== $original) {
            $disco->delete($originalAnterior);
        }

        $this->metricas->registrarProcessamento(true, (int) round(microtime(true) - $inicio), $this->bytesDaVersao($video, $versao));
    }

    /**
     * Registra a falha no estado do video: sem versao publicada vai a FALHOU; com
     * versao, volta a PRONTO mantendo a que os alunos ja assistem.
     */
    public function registrarFalha(Video $video, string $mensagem): void
    {
        $video->update([
            'status' => $video->temVersaoPublicada() ? VideoStatus::PRONTO : VideoStatus::FALHOU,
            'erro' => $mensagem,
            'tentativas' => $video->tentativas + 1,
        ]);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $acao
     * @return T
     */
    private function etapa(Video $video, string $stage, callable $acao): mixed
    {
        $inicio = microtime(true);
        $resultado = $acao();

        Log::channel('treinamento')->info('video.etapa', [
            'video_id' => $video->id,
            'stage' => $stage,
            'duracao_ms' => (int) round((microtime(true) - $inicio) * 1000),
            'bytes' => $video->tamanho_bytes,
        ]);

        return $resultado;
    }

    private function bytesDaVersao(Video $video, string $versao): int
    {
        $disco = Storage::disk('treinamentos');

        return (int) collect($disco->allFiles(CaminhosVideo::hlsVersao($video->id, $versao)))
            ->sum(fn (string $arquivo) => $disco->size($arquivo));
    }
}
