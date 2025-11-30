<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Sistema centralizado de logging de atividades
 * Registra todos os eventos do sistema para auditoria e monitoramento
 */
class ActivityLogger
{
    /**
     * Log de eventos do sistema
     */
    public static function logEvent(
        string $type,
        string $event,
        array $data = [],
        ?string $userId = null,
        string $level = 'info'
    ): void {
        $logData = [
            'timestamp' => now()->toIso8601String(),
            'type' => $type,
            'event' => $event,
            'data' => $data,
            'user_id' => $userId,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ];

        // Log em arquivo
        Log::channel('events')->{$level}($event, $logData);

        // Log em Redis para visualização em tempo real
        self::logToRedis($type, $logData);

        // Métricas para Prometheus
        self::incrementMetric($type, $event);
    }

    /**
     * Log específico para API
     */
    public static function logApiRequest(
        string $endpoint,
        int $statusCode,
        float $duration,
        ?int $userId = null,
        array $extra = []
    ): void {
        $data = array_merge([
            'endpoint' => $endpoint,
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'user_id' => $userId,
        ], $extra);

        self::logEvent('api', 'request', $data, $userId,
            $statusCode >= 500 ? 'error' : ($statusCode >= 400 ? 'warning' : 'info')
        );
    }

    /**
     * Log específico para webhooks
     */
    public static function logWebhook(
        string $direction, // 'incoming' ou 'outgoing'
        string $url,
        array $payload,
        int $statusCode,
        float $duration,
        bool $success
    ): void {
        self::logEvent('webhook', $direction, [
            'url' => $url,
            'payload_size' => strlen(json_encode($payload)),
            'status_code' => $statusCode,
            'duration_ms' => $duration,
            'success' => $success,
        ], null, $success ? 'info' : 'error');
    }

    /**
     * Log específico para integrações
     */
    public static function logIntegration(
        string $integrationType,
        string $action,
        bool $success,
        float $duration,
        array $extra = []
    ): void {
        self::logEvent('integration', $action, array_merge([
            'type' => $integrationType,
            'success' => $success,
            'duration_ms' => $duration,
        ], $extra), null, $success ? 'info' : 'error');
    }

    /**
     * Log de erros críticos
     */
    public static function logCriticalError(
        string $message,
        \Throwable $exception,
        array $context = []
    ): void {
        $data = array_merge([
            'message' => $message,
            'exception' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $context);

        self::logEvent('error', 'critical', $data, null, 'critical');

        // Notificar equipe (Slack, email, etc)
        // TODO: Implementar notificações
    }

    /**
     * Log de performance
     */
    public static function logPerformance(
        string $operation,
        float $duration,
        array $metrics = []
    ): void {
        $data = array_merge([
            'operation' => $operation,
            'duration_ms' => $duration,
        ], $metrics);

        self::logEvent('performance', $operation, $data);
    }

    /**
     * Log de segurança
     */
    public static function logSecurity(
        string $event,
        array $data = [],
        string $severity = 'warning'
    ): void {
        self::logEvent('security', $event, $data, null, $severity);
    }

    /**
     * Armazena logs no Redis para visualização em tempo real
     */
    private static function logToRedis(string $type, array $data): void
    {
        try {
            $key = "logs:{$type}";

            Redis::lpush($key, json_encode($data));
            Redis::ltrim($key, 0, 999); // Mantém últimos 1000 logs
            Redis::expire($key, 3600); // Expira em 1 hora
        } catch (\Exception $e) {
            Log::error('Failed to log to Redis', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Incrementa métricas para Prometheus
     */
    private static function incrementMetric(string $type, string $event): void
    {
        try {
            $key = "metrics:{$type}:{$event}";
            Redis::incr($key);
            Redis::expire($key, 300); // 5 minutos
        } catch (\Exception $e) {
            // Silencioso - não quebrar por falha em métrica
        }
    }

    /**
     * Obtém logs recentes do Redis
     */
    public static function getRecentLogs(string $type = 'all', int $limit = 100): array
    {
        try {
            if ($type === 'all') {
                $types = ['api', 'webhook', 'integration', 'error', 'performance', 'security'];
                $logs = [];

                foreach ($types as $t) {
                    $typeLogs = Redis::lrange("logs:{$t}", 0, $limit - 1);
                    foreach ($typeLogs as $log) {
                        $logs[] = json_decode($log, true);
                    }
                }

                // Ordena por timestamp
                usort($logs, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

                return array_slice($logs, 0, $limit);
            }

            $logs = Redis::lrange("logs:{$type}", 0, $limit - 1);
            return array_map(fn($log) => json_decode($log, true), $logs);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtém métricas
     */
    public static function getMetrics(): array
    {
        try {
            $keys = Redis::keys('metrics:*');
            $metrics = [];

            foreach ($keys as $key) {
                $parts = explode(':', $key);
                $type = $parts[1] ?? 'unknown';
                $event = $parts[2] ?? 'unknown';

                $metrics[] = [
                    'type' => $type,
                    'event' => $event,
                    'count' => Redis::get($key),
                ];
            }

            return $metrics;

        } catch (\Exception $e) {
            return [];
        }
    }
}
