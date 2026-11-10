<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Converte o original enviado em HLS. Casca: o handle() entra com o processamento.
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
}
