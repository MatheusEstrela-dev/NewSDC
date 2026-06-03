<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Throwable;

trait LogsExceptions
{
    protected function logException(Throwable $e, array $additionalContext = []): void
    {
        $layer = $this->detectLayer();
        $req = request();

        $context = array_merge([
            'exception_class' => get_class($e),
            'class' => static::class,
            'method' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? 'unknown',
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'layer' => $layer,
            'url' => $req ? $req->fullUrl() : 'cli',
            'http_method' => $req ? $req->method() : 'cli',
            'stack_trace' => $this->getShortStackTrace($e),
            'user_id' => auth()->id(),
        ], $additionalContext);

        Log::channel('db')->error($e->getMessage(), $context);
    }

    protected function logError(string $message, array $context = []): void
    {
        $layer = $this->detectLayer();
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $req = request();

        $context = array_merge([
            'class' => static::class,
            'method' => $backtrace[1]['function'] ?? 'unknown',
            'line' => $backtrace[0]['line'] ?? null,
            'file' => $backtrace[0]['file'] ?? null,
            'layer' => $layer,
            'url' => $req ? $req->fullUrl() : 'cli',
            'http_method' => $req ? $req->method() : 'cli',
            'user_id' => auth()->id(),
        ], $context);

        Log::channel('db')->error($message, $context);
    }

    protected function logWarning(string $message, array $context = []): void
    {
        $this->logWithLevel('warning', $message, $context);
    }

    protected function logInfo(string $message, array $context = []): void
    {
        $this->logWithLevel('info', $message, $context);
    }

    protected function logWithLevel(string $level, string $message, array $context = []): void
    {
        $layer = $this->detectLayer();
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        $context = array_merge([
            'class' => static::class,
            'method' => $backtrace[2]['function'] ?? 'unknown',
            'layer' => $layer,
            'user_id' => auth()->id(),
        ], $context);

        Log::channel('db')->{$level}($message, $context);
    }

    protected function detectLayer(): string
    {
        $class = static::class;

        if (str_contains($class, 'Controllers')) {
            return 'Controller';
        }
        if (str_contains($class, 'Services')) {
            return 'Service';
        }
        if (str_contains($class, 'Repositories')) {
            return 'Repository';
        }
        if (str_contains($class, 'Models')) {
            return 'Model';
        }
        if (str_contains($class, 'Requests')) {
            return 'Request';
        }
        if (str_contains($class, 'DTOs')) {
            return 'DTO';
        }
        if (str_contains($class, 'Middleware')) {
            return 'Middleware';
        }

        return 'Unknown';
    }

    protected function getShortStackTrace(Throwable $e, int $limit = 10): string
    {
        $lines = explode("\n", $e->getTraceAsString());
        return implode("\n", array_slice($lines, 0, $limit));
    }

    protected function executeWithLogging(callable $callback, string $operation = 'operation')
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            $this->logException($e, ['operation' => $operation]);
            throw $e;
        }
    }
}
