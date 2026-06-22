<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Logging\ActivityLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Grava um evento de auditoria/atividade FORA do hot path HTTP.
 *
 * Os middlewares de log (LogSystemActivity/LogApiRequests) apenas montam o array
 * de dados (barato) e despacham este job. O custo real do ActivityLogger
 * (debug_backtrace + ~5 round-trips Redis + escrita em arquivo) roda no worker de
 * fila, liberando o worker web imediatamente -- e o que tira os ~560ms/request do
 * teto da API (o gargalo medido no Ciclo 2). Auditoria continua completa, apenas
 * assincrona. Fila 'low' para nao competir com jobs de negocio.
 */
class RecordActivityLog implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string,mixed>  $data
     */
    public function __construct(
        public string $type,
        public string $event,
        public array $data,
        public ?string $userId = null,
        public string $level = 'info',
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        ActivityLogger::logEvent($this->type, $this->event, $this->data, $this->userId, $this->level);
    }
}
