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

        // Captura informacoes de origem uma unica vez
        $sourceInfo = self::getCallerInfo();

        $logData = [
            'timestamp' => now()->toIso8601String(),
            'event_type' => $type,
            'event_name' => $event,
            'severity' => $level,

            // Contexto da requisicao
            'request_id' => $requestId,
            'user_id' => $userId ?? auth()->id(),
            'ip_address' => app()->bound('request') ? request()->ip() : null,
            'user_agent' => app()->bound('request') ? request()->userAgent() : null,
            'url' => app()->bound('request') ? request()->fullUrl() : null,
            'http_method' => app()->bound('request') ? request()->method() : null,

            // Contexto do ambiente
            'environment' => config('app.env'),
            'app_name' => config('app.name'),
            'hostname' => function_exists('gethostname') ? gethostname() : (function_exists('php_uname') ? php_uname('n') : 'unknown'),

            // Dados do evento
            'data' => $data,

            // Contexto de codigo completo (para retrocompatibilidade)
            'source' => $sourceInfo,

            // Campos FLAT para compatibilidade direta com UI (LogViewerTable espera estes no root)
            'class' => $sourceInfo['class'],
            'method' => $sourceInfo['function'],
            'file' => $sourceInfo['file'],
            'file_path' => $sourceInfo['file_path'],
            'line' => $sourceInfo['line'],
            'layer' => $sourceInfo['layer'],
        ];

        try {
            // Log em arquivo estruturado
            Log::channel('events')->{$level}($event, $logData);

            // Log em Redis para visualização em tempo real
            self::logToRedis($type, $logData);

            // Métricas para Prometheus
            self::incrementMetric($type, $event);
        } catch (\Throwable $e) {
            // Failsafe: se o log falhar (ex: erro de permissão), não interromper a aplicação
            if (app()->environment('local', 'testing')) {
                error_log("ActivityLogger failed: " . $e->getMessage());
            }
        }
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
     * Obtém informações do codigo que chamou o log
     * Inclui path completo e layer para facilitar debug
     */
    private static function getCallerInfo(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);

        // Encontra o primeiro caller fora do ActivityLogger
        $caller = null;
        foreach ($trace as $frame) {
            $class = $frame['class'] ?? '';
            if ($class !== self::class && !str_contains($class, 'ActivityLogger')) {
                $caller = $frame;
                break;
            }
        }

        $caller = $caller ?? $trace[2] ?? $trace[1] ?? [];

        $file = $caller['file'] ?? null;
        $relativePath = $file ? str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file) : null;
        $relativePath = $relativePath ? str_replace('\\', '/', $relativePath) : null;

        $className = $caller['class'] ?? null;
        $layer = self::detectLayer($className, $relativePath);

        return [
            'file' => $relativePath ? basename($relativePath) : 'unknown',
            'file_path' => $relativePath,
            'line' => $caller['line'] ?? 0,
            'class' => $className,
            'function' => $caller['function'] ?? null,
            'layer' => $layer,
        ];
    }

    /**
     * Detecta a camada arquitetural no momento do log
     */
    private static function detectLayer(?string $className, ?string $filePath): string
    {
        $patterns = [
            'Controller' => ['Controller', 'Controllers'],
            'Service'    => ['Service', 'Services'],
            'Repository' => ['Repository', 'Repositories'],
            'Model'      => ['Model', 'Models'],
            'Middleware' => ['Middleware'],
            'Request'    => ['Request', 'Requests'],
            'Job'        => ['Job', 'Jobs'],
            'Event'      => ['Event', 'Events'],
            'Listener'   => ['Listener', 'Listeners'],
            'Command'    => ['Command', 'Commands'],
            'DTO'        => ['DTO', 'DataTransferObject'],
            'Resource'   => ['Resource', 'Resources'],
            'Policy'     => ['Policy', 'Policies'],
        ];

        $searchIn = ($className ?? '') . '|' . ($filePath ?? '');

        foreach ($patterns as $layer => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($searchIn, $keyword) !== false) {
                    return $layer;
                }
            }
        }

        return 'System';
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

        self::logEvent(
            'api',
            'request',
            $data,
            $userId,
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
        try {
            Log::channel('critical')->critical($message, $data);
        } catch (\Throwable $e) {
            // Silencioso
        }

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
        int $limit = 1000,
        ?string $file = null
    ): array {
        $logReader = app(\App\Services\Logging\LogFileReaderService::class);

        $logs = $logReader->readLogs([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'level' => $level,
            'search' => $search,
            'limit' => $limit,
            'file' => $file
        ]);

        return $logs->toArray();
    }

    /**
     * Obtém estatísticas agregadas dos logs
     */
    public static function getLogStatistics(
        \Carbon\Carbon $startDate,
        \Carbon\Carbon $endDate,
        ?string $type = null,
        ?string $file = null
    ): array {
        $logReader = app(\App\Services\Logging\LogFileReaderService::class);

        return $logReader->getStatistics([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'type' => $type,
            'file' => $file
        ]);
    }
}
