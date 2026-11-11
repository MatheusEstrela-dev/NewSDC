<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use Illuminate\Support\Facades\Log;

/** Metricas do video; por ora o resultado do processamento vai para o log estruturado. */
class VideoMetrics
{
    public function registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls): void
    {
        Log::channel('treinamento')->info('video.processamento', [
            'sucesso' => $sucesso,
            'duracao_s' => $segundos,
            'bytes' => $bytesHls,
        ]);
    }
}
