<?php

namespace App\Services\Logging;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Sistema centralizado de logging de atividades
 * Registra todos os eventos do sistema para auditoria e monitoramento
 *
 * Logs Estruturados para Produção 24/7
 * - Compatível com Grafana Loki, Datadog, ELK
 * - JSON estruturado com contexto completo
 * - Rastreamento por request_id
 */
class ActivityLogger
{
    /**
     * Log de eventos do sistema
     *
     * Responde as 5 perguntas do "Log Perfeito":
     * 1. Timestamp: Quando aconteceu?
     * 2. Level: Qual a gravidade?
     * 3. Context: Quem foi o usuário e qual o Request ID?
     * 4. Message: O que aconteceu?
     * 5. Trace: Onde no código?
     */
    public static function logEvent(
        string $type,
        string $event,
        array $data = [],
        ?string $userId = null,
        string $level = 'info'
    ): void {
        // Obtém request_id do contexto global (definido no AppServiceProvider)
        $requestId = self::getRequestId();

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'event_type' => $type,
            'event_name' => $event,
            'severity' => $level,

            // Contexto da requisição
            'request_id' => $requestId,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'http_method' => request()?->method(),

            // Contexto do ambiente
            'environment' => config('app.env'),
            'app_name' => config('app.name'),
            'hostname' => gethostname(),

            // Dados do evento
            'data' => $data,

            // Contexto de código (útil para debugging)
            'source' => self::getCallerInfo(),
        ];

        // Log em arquivo estruturado
        Log::channel('events')->{$level}($event, $logData);

        // Log em Redis para visualização em tempo real
        self::logToRedis($type, $logData);

        // Métricas para Prometheus
        self::incrementMetric($type, $event);
    }

    /**
     * Obtém o request_id do contexto global
     */
    private static function getRequestId(): string
    {
        // Laravel 11+
        if (class_exists(\Illuminate\Support\Facades\Context::class)) {
            return \Illuminate\Support\Facades\Context::get('request_id')
                ?? request()?->header('X-Request-ID')
                ?? 'unknown';
        }

        // Laravel 10 e anteriores
        return request()?->header('X-Request-ID') ?? 'unknown';
    }

    /**
     * Obtém informações do código que chamou o log
     */
    private static function getCallerInfo(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $trace[2] ?? $trace[1] ?? [];

        return [
            'file' => basename($caller['file'] ?? 'unknown'),
            'line' => $caller['line'] ?? 0,
            'class' => $caller['class'] ?? null,
            'function' => $caller['function'] ?? null,
        ];
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
     * Log de erros críticos com stack trace completo
     *
     * Estrutura compatível com Sentry/Datadog para análise de erros
     */
    public static function logCriticalError(
        string $message,
        \Throwable $exception,
        array $context = []
    ): void {
        $data = array_merge([
            'error_message' => $message,
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_code' => $exception->getCode(),

            // Localização do erro
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),

            // Stack trace estruturado (primeiras 10 chamadas)
            'stack_trace' => collect($exception->getTrace())
                ->take(10)
                ->map(fn($trace) => [
                    'file' => basename($trace['file'] ?? 'unknown'),
                    'line' => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? 'unknown',
                    'class' => $trace['class'] ?? null,
                ])
                ->toArray(),

            // Trace completo como string (para logs de texto)
            'full_trace' => $exception->getTraceAsString(),

            // Exception anterior (se existir)
            'previous_exception' => $exception->getPrevious() ? [
                'class' => get_class($exception->getPrevious()),
                'message' => $exception->getPrevious()->getMessage(),
                'file' => $exception->getPrevious()->getFile(),
                'line' => $exception->getPrevious()->getLine(),
            ] : null,

            // Métricas de sistema no momento do erro
            'system_metrics' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'memory_limit' => ini_get('memory_limit'),
                'php_version' => PHP_VERSION,
            ],
        ], $context);

        self::logEvent('error', 'critical_error', $data, null, 'critical');

        // Log também no canal critical separado
        Log::channel('critical')->critical($message, $data);

        // Notificar equipe (Slack, email, etc)
        // TODO: Implementar notificações via Slack/Discord
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
            // Verificar se Redis está disponível
            if (!class_exists('Redis') && !class_exists('Predis\Client')) {
                return; // Redis não disponível, skip silenciosamente
            }

            $key = "logs:{$type}";

            Redis::lpush($key, json_encode($data));
            Redis::ltrim($key, 0, 999); // Mantém últimos 1000 logs
            Redis::expire($key, 3600); // Expira em 1 hora
        } catch (\Exception $e) {
            // Silencioso - não logar erro para evitar loop infinito
        }
    }

    /**
     * Incrementa métricas para Prometheus
     */
    private static function incrementMetric(string $type, string $event): void
    {
        try {
            // Verificar se Redis está disponível
            if (!class_exists('Redis') && !class_exists('Predis\Client')) {
                return; // Redis não disponível, skip silenciosamente
            }

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

    /**
     * Busca logs por intervalo de datas (fallback para arquivos)
     * Quando Redis não tem dados históricos, busca nos arquivos de log
     */
    public static function getLogsByDateRange(
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        ?string $type = null,
        ?string $level = null,
        ?string $search = null,
        int $limit = 1000
    ): array {
        $logReader = app(\App\Services\Logging\LogFileReaderService::class);

        $logs = $logReader->readLogs([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'level' => $level,
            'search' => $search,
            'limit' => $limit,
        ]);

        return $logs->toArray();
    }

    /**
     * Obtém estatísticas agregadas dos logs
     */
    public static function getLogStatistics(
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        ?string $type = null
    ): array {
        $logReader = app(\App\Services\Logging\LogFileReaderService::class);

        return $logReader->getStatistics([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
        ]);
    }
}
