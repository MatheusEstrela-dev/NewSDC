<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Traits\LogsActivity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogRequestLifecycle
{
    use LogsActivity;

    protected array $excludedPaths = [
        '_debugbar',
        'telescope',
        'horizon',
        'livewire',
        'sanctum',
    ];

    protected array $excludedExtensions = [
        'js', 'css', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $requestId = $request->header('X-Request-ID') ?? Str::uuid()->toString();
        $request->headers->set('X-Request-ID', $requestId);
        static::setRequestId($requestId);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $this->logRequestStart($request, $requestId);

        try {
            $response = $next($request);

            $this->logRequestEnd($request, $response, $requestId, $startTime, $startMemory);

            $response->headers->set('X-Request-ID', $requestId);

            return $response;
        } catch (\Throwable $e) {
            $this->logRequestException($request, $e, $requestId, $startTime);
            throw $e;
        } finally {
            static::resetRequestId();
        }
    }

    protected function shouldSkip(Request $request): bool
    {
        $path = $request->path();

        foreach ($this->excludedPaths as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array(strtolower($extension), $this->excludedExtensions)) {
            return true;
        }

        return false;
    }

    protected function logRequestStart(Request $request, string $requestId): void
    {
        $context = [
            'request_id' => $requestId,
            'event' => 'request_start',
            'layer' => 'Middleware',
            'class' => static::class,
            'method' => 'handle',
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'route' => $request->route()?->getName() ?? 'unknown',
            'controller' => $request->route()?->getActionName() ?? 'unknown',
            'ip' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 255),
            'user_id' => $request->user()?->id,
            'content_type' => $request->header('Content-Type'),
            'accept' => $request->header('Accept'),
            'request_data' => $this->sanitizeRequestData($request),
        ];

        Log::channel('db')->info("Request started: {$request->method()} {$request->path()}", $context);
    }

    protected function logRequestEnd(
        Request $request,
        Response $response,
        string $requestId,
        float $startTime,
        int $startMemory
    ): void {
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);
        $memoryUsed = memory_get_usage(true) - $startMemory;
        $statusCode = $response->getStatusCode();

        $level = $this->getLogLevelForStatus($statusCode);

        $context = [
            'request_id' => $requestId,
            'event' => 'request_end',
            'layer' => 'Middleware',
            'class' => static::class,
            'method' => 'handle',
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'status_code' => $statusCode,
            'duration_ms' => $durationMs,
            'memory_usage' => $memoryUsed,
            'memory_peak' => memory_get_peak_usage(true),
            'user_id' => $request->user()?->id,
            'response_size' => strlen($response->getContent() ?: ''),
        ];

        if ($durationMs > 2000) {
            $context['slow_request'] = true;
        }

        Log::channel('db')->{$level}(
            "Request completed: {$request->method()} {$request->path()} [{$statusCode}] {$durationMs}ms",
            $context
        );
    }

    protected function logRequestException(
        Request $request,
        \Throwable $e,
        string $requestId,
        float $startTime
    ): void {
        $durationMs = round((microtime(true) - $startTime) * 1000, 2);

        $context = [
            'request_id' => $requestId,
            'event' => 'request_exception',
            'layer' => 'Middleware',
            'class' => static::class,
            'method' => 'handle',
            'url' => $request->fullUrl(),
            'http_method' => $request->method(),
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->id,
            'exception_class' => get_class($e),
            'exception_message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'stack_trace' => $this->getShortStackTrace($e),
        ];

        Log::channel('db')->error(
            "Request exception: {$request->method()} {$request->path()} - {$e->getMessage()}",
            $context
        );
    }

    protected function sanitizeRequestData(Request $request): string
    {
        $data = $request->except(['password', 'password_confirmation', 'current_password', 'token', 'secret']);

        $sanitized = $this->sanitizeParams($data);

        $json = json_encode($sanitized, JSON_UNESCAPED_UNICODE);

        if (strlen($json) > 5000) {
            return substr($json, 0, 5000) . '...[truncated]';
        }

        return $json;
    }

    protected function getLogLevelForStatus(int $statusCode): string
    {
        return match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            default => 'info',
        };
    }
}
