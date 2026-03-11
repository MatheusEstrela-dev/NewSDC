<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

trait LogsActivity
{
    protected static ?string $currentRequestId = null;

    protected function getRequestId(): string
    {
        if (static::$currentRequestId === null) {
            static::$currentRequestId = request()->header('X-Request-ID')
                ?? Str::uuid()->toString();
        }
        return static::$currentRequestId;
    }

    protected function detectLayer(): string
    {
        $class = static::class;

        $layers = [
            'Controllers' => 'Controller',
            'Services' => 'Service',
            'Repositories' => 'Repository',
            'Models' => 'Model',
            'Requests' => 'Request',
            'DTOs' => 'DTO',
            'DTO' => 'DTO',
            'Middleware' => 'Middleware',
            'Observers' => 'Observer',
            'Jobs' => 'Job',
            'Events' => 'Event',
            'Listeners' => 'Listener',
        ];

        foreach ($layers as $namespace => $layer) {
            if (str_contains($class, $namespace)) {
                return $layer;
            }
        }

        return 'Unknown';
    }

    protected function getBaseContext(string $method): array
    {
        $req = request();

        return [
            'request_id' => $this->getRequestId(),
            'class' => static::class,
            'method' => $method,
            'layer' => $this->detectLayer(),
            'user_id' => Auth::id(),
            'url' => $req ? $req->fullUrl() : 'cli',
            'http_method' => $req ? $req->method() : 'cli',
        ];
    }

    protected function logEntry(string $method, array $params = []): void
    {
        $context = $this->getBaseContext($method);
        $context['event'] = 'entry';
        $context['params'] = $this->sanitizeParams($params);
        $context['memory_start'] = memory_get_usage(true);
        $context['time_start'] = microtime(true);

        Log::channel('db')->debug("Entering {$context['class']}::{$method}", $context);
    }

    protected function logExit(string $method, mixed $result = null, ?float $startTime = null): void
    {
        $context = $this->getBaseContext($method);
        $context['event'] = 'exit';

        if ($startTime !== null) {
            $context['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);
        }

        $context['memory_usage'] = memory_get_usage(true);
        $context['result_type'] = $result !== null ? gettype($result) : 'void';

        Log::channel('db')->debug("Exiting {$context['class']}::{$method}", $context);
    }

    protected function logInfo(string $message, array $additionalContext = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $method = $backtrace[1]['function'] ?? 'unknown';

        $context = array_merge($this->getBaseContext($method), $additionalContext);

        Log::channel('db')->info($message, $context);
    }

    protected function logWarning(string $message, array $additionalContext = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $method = $backtrace[1]['function'] ?? 'unknown';

        $context = array_merge($this->getBaseContext($method), $additionalContext);

        Log::channel('db')->warning($message, $context);
    }

    protected function logError(string $message, ?Throwable $e = null, array $additionalContext = []): void
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $method = $backtrace[1]['function'] ?? 'unknown';

        $context = array_merge($this->getBaseContext($method), $additionalContext);

        if ($e !== null) {
            $context['exception_class'] = get_class($e);
            $context['exception_message'] = $e->getMessage();
            $context['file'] = $e->getFile();
            $context['line'] = $e->getLine();
            $context['stack_trace'] = $this->getShortStackTrace($e);
        }

        Log::channel('db')->error($message, $context);
    }

    protected function logException(Throwable $e, array $additionalContext = []): void
    {
        $this->logError($e->getMessage(), $e, $additionalContext);
    }

    protected function wrapWithLogging(string $operation, callable $callback): mixed
    {
        $startTime = microtime(true);
        $this->logEntry($operation);

        try {
            $result = $callback();
            $this->logExit($operation, $result, $startTime);
            return $result;
        } catch (Throwable $e) {
            $this->logException($e, ['operation' => $operation]);
            throw $e;
        }
    }

    protected function executeWithLogging(string $operation, callable $callback): mixed
    {
        return $this->wrapWithLogging($operation, $callback);
    }

    protected function sanitizeParams(array $params): array
    {
        $sensitiveKeys = [
            'password', 'password_confirmation', 'current_password',
            'token', 'secret', 'api_key', 'credit_card', 'cvv', 'ssn',
            'cpf', 'cnpj', 'card_number', 'authorization',
        ];

        $sanitized = [];
        foreach ($params as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParams($value);
            } elseif (is_string($value) && strlen($value) > 500) {
                $sanitized[$key] = substr($value, 0, 500) . '...[truncated]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    protected function getShortStackTrace(Throwable $e, int $limit = 10): string
    {
        $lines = explode("\n", $e->getTraceAsString());
        return implode("\n", array_slice($lines, 0, $limit));
    }

    public static function setRequestId(string $requestId): void
    {
        static::$currentRequestId = $requestId;
    }

    public static function resetRequestId(): void
    {
        static::$currentRequestId = null;
    }
}
