<?php

namespace App\Http\Middleware;

use App\Services\Logging\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para logging global de TODAS as atividades do sistema
 * Captura requisições Web e API para auditoria completa
 */
class LogSystemActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Ignorar assets e rotas de debug/log para evitar loop
        if ($this->shouldIgnore($request)) {
            return $next($request);
        }

        $startTime = microtime(true);

        // Executa a requisição
        $response = $next($request);

        // Calcula duração
        $duration = (microtime(true) - $startTime) * 1000; // em ms

        // Determina o tipo de evento (API ou WEB)
        $type = $request->expectsJson() ? 'api_request' : 'web_request';

        // Log detalhado
        ActivityLogger::logEvent(
            type: 'system_activity',
            event: $type,
            data: [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => auth()->id() ?? 'guest',
                'inputs' => $request->except(['password', 'password_confirmation', '_token']),
            ],
            userId: auth()->id() ? (string) auth()->id() : null,
            level: $response->getStatusCode() >= 400 ? 'warning' : 'info'
        );

        return $response;
    }

    /**
     * Define rotas que não devem ser logadas
     */
    protected function shouldIgnore(Request $request): bool
    {
        $patterns = [
            '/_debugbar/*',
            '/log-viewer*',
            '/logs*', // Não logar o próprio visualizador de logs
            '/_ignition/*',
            '*.js',
            '*.css',
            '*.png',
            '*.jpg',
            '*.ico',
            '*.svg',
            '*.woff',
            '*.woff2',
        ];

        foreach ($patterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
