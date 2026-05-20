<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\RequestTrace;
use Throwable;

/**
 * Trait para jobs que sao despachados via AsynchronousResponse::dispatchAsyncJob.
 * Recebe um trace_id no construtor e atualiza status conforme o ciclo de vida.
 *
 * Uso:
 *
 *   class MeuJob implements ShouldQueue
 *   {
 *       use TracksAsyncProgress;
 *
 *       public function __construct(string $traceId, private array $params)
 *       {
 *           $this->setTrace($traceId);
 *       }
 *
 *       public function handle(): void
 *       {
 *           $this->runTracked(function () {
 *               // ... processamento pesado ...
 *               return ['disk' => 'exports', 'path' => 'foo.xlsx'];
 *           });
 *       }
 *   }
 */
trait TracksAsyncProgress
{
    protected ?string $traceId = null;

    public function setTrace(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    /**
     * Executa o callback marcando o trace como processing antes,
     * completed (com result_disk/result_path) ou failed depois.
     *
     * O callback deve retornar array ['disk' => string, 'path' => string]
     * quando houver artefato a baixar; null caso contrario.
     */
    protected function runTracked(callable $work): void
    {
        $trace = $this->traceId ? RequestTrace::find($this->traceId) : null;

        $trace?->markAsProcessing();

        try {
            $result = $work();

            if (is_array($result) && isset($result['disk'], $result['path'])) {
                $trace?->markAsCompleted($result['disk'], $result['path']);
            } else {
                $trace?->markAsCompleted();
            }
        } catch (Throwable $e) {
            $trace?->markAsFailed($e->getMessage());
            throw $e;
        }
    }
}
