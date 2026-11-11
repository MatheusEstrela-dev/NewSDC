<?php

declare(strict_types=1);

namespace App\Modules\Treinamento\Services\Video;

use Illuminate\Support\Facades\Redis;

/**
 * Contadores do pipeline de video no Redis compartilhado (valem para todas as
 * replicas). Falha de Redis nunca derruba o processamento nem o scrape.
 */
class VideoMetrics
{
    public function __construct(private readonly string $prefixo = 'treinamento:video:') {}

    public function registrarProcessamento(bool $sucesso, int $segundos, int $bytesHls): void
    {
        $this->tentar(function ($redis) use ($sucesso, $segundos, $bytesHls) {
            $redis->incr($this->chave($sucesso ? 'processados:sucesso' : 'processados:falha'));
            $redis->incrby($this->chave('processamento_segundos_sum'), $segundos);
            $redis->incr($this->chave('processamento_segundos_count'));

            if ($bytesHls > 0) {
                $redis->incrby($this->chave('hls_bytes'), $bytesHls);
            }
        });
    }

    public function registrarRemocao(int $bytes): void
    {
        $this->tentar(function ($redis) use ($bytes) {
            $restante = (int) $redis->decrby($this->chave('hls_bytes'), $bytes);

            // O gauge nunca fica negativo (ex.: HLS publicado antes das metricas existirem).
            if ($restante < 0) {
                $redis->incrby($this->chave('hls_bytes'), -$restante);
            }
        });
    }

    /** @return list<string> linhas no formato texto do Prometheus */
    public function linhas(): array
    {
        $valores = $this->tentar(fn ($redis) => [
            'sucesso' => (int) $redis->get($this->chave('processados:sucesso')),
            'falha' => (int) $redis->get($this->chave('processados:falha')),
            'soma' => (int) $redis->get($this->chave('processamento_segundos_sum')),
            'total' => (int) $redis->get($this->chave('processamento_segundos_count')),
            'bytes' => (int) $redis->get($this->chave('hls_bytes')),
        ]);

        if ($valores === null) {
            return [];
        }

        return [
            '# HELP sdc_treinamento_video_processados_total Videos processados, por resultado',
            '# TYPE sdc_treinamento_video_processados_total counter',
            "sdc_treinamento_video_processados_total{resultado=\"sucesso\"} {$valores['sucesso']}",
            "sdc_treinamento_video_processados_total{resultado=\"falha\"} {$valores['falha']}",
            '# HELP sdc_treinamento_video_processamento_segundos Duracao do processamento de video',
            '# TYPE sdc_treinamento_video_processamento_segundos summary',
            "sdc_treinamento_video_processamento_segundos_sum {$valores['soma']}",
            "sdc_treinamento_video_processamento_segundos_count {$valores['total']}",
            '# HELP sdc_treinamento_video_hls_bytes Bytes de HLS publicados',
            '# TYPE sdc_treinamento_video_hls_bytes gauge',
            "sdc_treinamento_video_hls_bytes {$valores['bytes']}",
        ];
    }

    private function chave(string $nome): string
    {
        return $this->prefixo.$nome;
    }

    /** Executa contra o Redis e devolve null se ele estiver indisponivel. */
    private function tentar(callable $acao): mixed
    {
        try {
            return $acao(Redis::connection());
        } catch (\Throwable) {
            return null;
        }
    }
}
