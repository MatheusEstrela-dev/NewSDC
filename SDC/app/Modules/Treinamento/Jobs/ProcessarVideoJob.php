<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use App\Modules\Treinamento\Exceptions\FalhaDeVideo;
use App\Modules\Treinamento\Models\Video;
use App\Modules\Treinamento\Models\VideoUpload;
use App\Modules\Treinamento\Services\Video\ProcessadorDeVideo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Converte o original enviado em HLS e publica uma nova versao do video.
 */
class ProcessarVideoJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    public int $uniqueFor = 7200;

    public function __construct(public int $videoId, public string $uploadId)
    {
        $this->onConnection(config('treinamento.video.conexao_fila'));
        $this->onQueue(config('treinamento.video.fila'));
    }

    public function uniqueId(): string
    {
        return "video-{$this->videoId}";
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return [120];
    }

    public function handle(ProcessadorDeVideo $processador): void
    {
        $video = Video::query()->find($this->videoId);
        $upload = VideoUpload::query()
            ->whereKey($this->uploadId)
            ->where('video_id', $this->videoId)
            ->where('status', VideoUpload::COMPLETO)
            ->first();

        if ($video === null || $upload === null) {
            return;
        }

        try {
            $processador->processar($video, $upload);
        } catch (FalhaDeVideo $e) {
            // Arquivo invalido: repetir nao adianta. As demais falhas sobem para o retry.
            if (! $e->definitiva) {
                throw $e;
            }

            $this->fail($e);
        }
    }

    /** Timeout, kill ou esgotamento das tentativas: o video nunca fica preso em processamento. */
    public function failed(\Throwable $e): void
    {
        $video = Video::query()->find($this->videoId);

        if ($video !== null && $video->status->emProcessamento()) {
            app(ProcessadorDeVideo::class)->registrarFalha($video, $e->getMessage());
        }
    }
}
