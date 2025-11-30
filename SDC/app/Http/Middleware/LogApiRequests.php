<?php

namespace App\Http\Middleware;

use App\Services\Logging\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para logging automático de TODAS as requisições API
 * Sistema Crítico 24/7 - Auditoria Completa
 */
class LogApiRequests
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Executa a requisição
        $response = $next($request);

        // Calcula duração
        $duration = (microtime(true) - $startTime) * 1000; // em ms

        // Log detalhado da requisição
        ActivityLogger::logApiRequest(
            endpoint: $request->path(),
            statusCode: $response->getStatusCode(),
            duration: $duration,
            userId: auth()->id(),
            extra: [
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_id' => $request->header('X-Request-ID') ?? uniqid(),
                'query_params' => $request->query(),
                'has_body' => $request->getContent() ? true : false,
                'response_size' => strlen($response->getContent()),
            ]
        );

        // Log queries lentas (> 500ms para API)
        if ($duration > 500) {
            ActivityLogger::logPerformance(
                operation: 'api_slow_response',
                duration: $duration,
                metrics: [
                    'endpoint' => $request->path(),
                    'method' => $request->method(),
                    'status_code' => $response->getStatusCode(),
                ]
            );
        }

        return $response;
    }
}
