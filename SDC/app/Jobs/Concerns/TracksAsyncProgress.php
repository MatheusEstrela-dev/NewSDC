<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\RequestTrace;
use App\Notifications\GeneralNotification;
use Illuminate\Support\Facades\Notification;
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

            if ($trace) {
                $this->notifyUser($trace, success: true);
            }
        } catch (Throwable $e) {
            $trace?->markAsFailed($e->getMessage());

            if ($trace) {
                $this->notifyUser($trace, success: false, error: $e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Notifica o user dono do trace (se existir) via GeneralNotification:
     * database (inbox in-app) + broadcast (push websocket). Skip se trace
     * for "anonimo" (sem user_id) ou se user nao tiver inbox configurado.
     */
    private function notifyUser(RequestTrace $trace, bool $success, ?string $error = null): void
    {
        $user = $trace->user;
        if (!$user) {
            return;
        }

        if ($success) {
            $title = 'Processamento concluido';
            $message = sprintf('Seu export "%s" esta pronto.', $trace->type);
            $type = 'success';
            $actionUrl = $trace->hasResult()
                ? route('api.v1.traces.download', ['traceId' => $trace->id])
                : route('api.v1.traces.show', ['traceId' => $trace->id]);
            $actionText = $trace->hasResult() ? 'Baixar arquivo' : 'Ver status';
        } else {
            $title = 'Processamento falhou';
            $message = sprintf(
                'Seu export "%s" falhou: %s',
                $trace->type,
                mb_substr((string) $error, 0, 200)
            );
            $type = 'error';
            $actionUrl = route('api.v1.traces.show', ['traceId' => $trace->id]);
            $actionText = 'Ver detalhes';
        }

        try {
            Notification::send($user, new GeneralNotification(
                title: $title,
                message: $message,
                type: $type,
                actionUrl: $actionUrl,
                actionText: $actionText,
                groupKey: "trace:{$trace->type}",
            ));
        } catch (Throwable) {
            // Notificacao eh best-effort; nao queremos derrubar o job se
            // o broadcast falhar (ex: websocket server offline).
        }
    }
}
