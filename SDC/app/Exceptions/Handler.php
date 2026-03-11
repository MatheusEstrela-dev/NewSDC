<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        AuthenticationException::class,
        ValidationException::class,
        NotFoundHttpException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            $this->logDetailedException($e);
        })->stop();
    }

    protected function logDetailedException(Throwable $e): void
    {
        try {
            $trace = $e->getTrace();
            $origin = $this->findOriginLayer($trace);
            $req = request();

            $context = [
                'exception_class' => get_class($e),
                'class' => $origin['class'],
                'method' => $origin['method'],
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'layer' => $origin['layer'],
                'url' => $req ? $req->fullUrl() : 'cli',
                'http_method' => $req ? $req->method() : 'cli',
                'request_data' => $req ? $this->sanitizeRequestData($req->except($this->dontFlash)) : '{}',
                'stack_trace' => $this->getShortStackTrace($e),
                'user_id' => auth()->id(),
            ];

            Log::channel('db')->error($e->getMessage(), $context);
        } catch (\Exception $logError) {
            Log::channel('daily')->error('Falha ao logar excecao detalhada: ' . $logError->getMessage());
            Log::channel('daily')->error($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }
    }

    protected function findOriginLayer(array $trace): array
    {
        $layers = [
            'Controller' => 'app/Http/Controllers',
            'Service' => 'app/Services',
            'Repository' => 'app/Repositories',
            'Model' => 'app/Models',
            'Request' => 'app/Http/Requests',
            'DTO' => 'app/DTOs',
            'Middleware' => 'app/Http/Middleware',
        ];

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? '';
            foreach ($layers as $layer => $path) {
                if (strpos($file, $path) !== false) {
                    return [
                        'layer' => $layer,
                        'class' => $frame['class'] ?? basename($file),
                        'method' => $frame['function'] ?? 'unknown',
                    ];
                }
            }
        }

        if (empty($trace)) {
            return [
                'layer' => 'Unknown',
                'class' => 'Unknown',
                'method' => 'unknown',
            ];
        }

        return [
            'layer' => 'Unknown',
            'class' => $trace[0]['class'] ?? 'Unknown',
            'method' => $trace[0]['function'] ?? 'unknown',
        ];
    }

    protected function getShortStackTrace(Throwable $e, int $limit = 10): string
    {
        $lines = explode("\n", $e->getTraceAsString());
        return implode("\n", array_slice($lines, 0, $limit));
    }

    protected function sanitizeRequestData(array $data): string
    {
        $sanitized = array_map(function ($value) {
            if (is_string($value) && strlen($value) > 500) {
                return substr($value, 0, 500) . '...[truncated]';
            }
            return $value;
        }, $data);

        return json_encode($sanitized, JSON_UNESCAPED_UNICODE);
    }

    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }
}
