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

        // Executa a requisicao
        $response = $next($request);

        // Log assincrono via terminate para nao bloquear a resposta
        // O registro acontece APOS a resposta ser enviada ao cliente
        app()->terminating(function () use ($request, $response, $startTime) {
            $duration = (microtime(true) - $startTime) * 1000;
            $type = $request->expectsJson() ? 'api_request' : 'web_request';

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
                    'user_id' => auth()->id() ?? 'guest',
                ],
                userId: auth()->id() ? (string) auth()->id() : null,
                level: $response->getStatusCode() >= 400 ? 'warning' : 'info'
            );
        });

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
