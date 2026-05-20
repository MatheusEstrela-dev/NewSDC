<?php

namespace App\Http\Controllers\Traits;

use App\Models\RequestTrace;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Trait para Respostas Assíncronas (Fire & Forget)
 *
 * Baseado no papiro.md - Arquitetura Assíncrona
 *
 * Para APIs de alta ingestão (webhooks, logs, dados de sensores),
 * você nunca processa na hora.
 *
 * Fluxo Técnico:
 * 1. Valida o básico (OpenAPI)
 * 2. Serializa o Payload
 * 3. Enfileira (RabbitMQ ou Redis Queue)
 * 4. Responde imediatamente
 *
 * Use este trait em controllers que processam dados de forma assíncrona.
 */
trait AsynchronousResponse
{
    /**
     * Retorna uma resposta 202 Accepted com Trace ID
     *
     * Padrão correto para processamento assíncrono:
     * - Status 202 (Accepted, não 200 OK)
     * - trace_id para rastreamento
     * - estimated_processing para expectativa do cliente
     *
     * @param string|null $traceId UUID de rastreamento (gera automaticamente se null)
     * @param string $message Mensagem de sucesso
     * @param array $extra Dados extras para incluir na resposta
     * @param int $estimatedSeconds Tempo estimado de processamento em segundos
     * @return JsonResponse
     */
    protected function acceptedResponse(
        ?string $traceId = null,
        string $message = 'Request accepted and queued for processing',
        array $extra = [],
        int $estimatedSeconds = 30
    ): JsonResponse {
        $traceId = $traceId ?? Str::uuid()->toString();

        $response = [
            'status' => 'accepted',
            'message' => $message,
            'trace_id' => $traceId,
            'estimated_processing' => $this->formatEstimatedTime($estimatedSeconds),
            ...$extra,
        ];

        return response()->json($response, 202);
    }

    /**
     * Formata o tempo estimado de processamento
     */
    private function formatEstimatedTime(int $seconds): string
    {
        if ($seconds < 60) {
            return "within {$seconds} seconds";
        }

        if ($seconds < 3600) {
            $minutes = ceil($seconds / 60);
            return "within {$minutes} minutes";
        }

        $hours = ceil($seconds / 3600);
        return "within {$hours} hours";
    }

    /**
     * Gera um Trace ID (Correlation ID) único
     *
     * Use para rastrear o fluxo completo de uma requisição
     * através de múltiplos serviços e filas.
     *
     * @return string UUID v4
     */
    protected function generateTraceId(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Despacha um job assincrono criando um RequestTrace persistido.
     *
     * O job deve usar App\Jobs\Concerns\TracksAsyncProgress e aceitar o
     * trace_id como primeiro argumento do construtor. O cliente consulta
     * status via GET /api/v1/traces/{traceId}.
     *
     * @param string $jobClass FQCN do job a despachar
     * @param string $type identificador do tipo (ex: 'export_decretacoes')
     * @param array<int, mixed> $args argumentos adicionais do construtor (apos $traceId)
     * @param array<string, mixed> $meta payload arbitrario salvo no trace
     * @param string $queue queue de destino (default 'bulk' para baixa prioridade)
     * @param int $estimatedSeconds tempo estimado para o cliente
     */
    protected function dispatchAsyncJob(
        string $jobClass,
        string $type,
        array $args = [],
        array $meta = [],
        string $queue = 'bulk',
        int $estimatedSeconds = 60,
    ): JsonResponse {
        $traceId = $this->generateTraceId();

        RequestTrace::create([
            'id' => $traceId,
            'user_id' => optional(request()->user())->id,
            'type' => $type,
            'status' => RequestTrace::STATUS_PENDING,
            'meta' => $meta,
        ]);

        $job = new $jobClass($traceId, ...$args);
        app(Dispatcher::class)->dispatch($job->onQueue($queue));

        return $this->acceptedResponse(
            traceId: $traceId,
            message: 'Request enqueued; consulte status via GET /api/v1/traces/{traceId}',
            extra: ['type' => $type],
            estimatedSeconds: $estimatedSeconds,
        );
    }

    /**
     * Retorna resposta de erro assíncrona (mas ainda com trace_id)
     *
     * @param string $message Mensagem de erro
     * @param string|null $traceId Trace ID da requisição
     * @param int $statusCode Status HTTP (padrão 400)
     * @param array $extra Dados extras
     * @return JsonResponse
     */
    protected function asyncErrorResponse(
        string $message,
        ?string $traceId = null,
        int $statusCode = 400,
        array $extra = []
    ): JsonResponse {
        $traceId = $traceId ?? Str::uuid()->toString();

        $response = [
            'error' => class_basename(static::class) . ' Error',
            'message' => $message,
            'trace_id' => $traceId,
            ...$extra,
        ];

        return response()->json($response, $statusCode);
    }
}
