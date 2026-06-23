<?php

namespace App\Http\Middleware;

use App\Jobs\RecordActivityLog;
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

        // Auditoria FORA do hot path: monta o array (barato) e DESPACHA pra fila.
        // O custo real do ActivityLogger (debug_backtrace + Redis + arquivo) roda
        // no worker de fila, liberando o worker web. Antes isto rodava no
        // terminating() -- que, sob Octane, ocupa o worker antes do proximo request
        // (nao ajudava o throughput). Agora e so um push.
        $duration = (microtime(true) - $startTime) * 1000;
        $type = $request->expectsJson() ? 'api_request' : 'web_request';
        $userId = auth()->id();

        RecordActivityLog::dispatch(
            'system_activity',
            $type,
            [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'status_code' => $response->getStatusCode(),
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'user_id' => $userId ?? 'guest',
            ],
            $userId ? (string) $userId : null,
            $response->getStatusCode() >= 400 ? 'warning' : 'info',
        );

        return $response;
    }

    /**
     * Define rotas que não devem ser logadas
     */
    protected function shouldIgnore(Request $request): bool
    {
        $patterns = [
            // Endpoints de infra/monitoramento: ping, nao evento de auditoria
            // (e o LB martela /api/health). Sem barra inicial: $request->is()
            // casa contra o path sem barra.
            'health',
            'api/health',
            'api/health/*',
            'metrics',
            'api/metrics',
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
