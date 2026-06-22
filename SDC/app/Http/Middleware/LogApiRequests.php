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

        $status = $response->getStatusCode();

        // Log detalhado da requisição (amostrado em 2xx; ver shouldLog)
        if ($this->shouldLog($request, $status)) {
            ActivityLogger::logApiRequest(
                endpoint: $request->path(),
                statusCode: $status,
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
        }

        // Log queries lentas (> 500ms para API) -- sempre, independe do sampling
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

    /**
     * Decide se a request entra no log de auditoria de API.
     *
     * Regras (nessa ordem):
     *  - endpoints de infra (health/metrics): nunca -- ping nao e evento de negocio
     *    e o LB martela /api/health, dominando o custo de log no hot path;
     *  - status >= 400: sempre -- auditoria completa de erros (4xx/5xx);
     *  - 2xx: amostrado por logging.api_request_sample_rate (1 = todas; N = ~1/N).
     */
    private function shouldLog(Request $request, int $status): bool
    {
        if ($this->isMonitoringEndpoint($request)) {
            return false;
        }

        if ($status >= 400) {
            return true;
        }

        $rate = (int) config('logging.api_request_sample_rate', 1);

        if ($rate <= 1) {
            return true;
        }

        return random_int(1, $rate) === 1;
    }

    /**
     * Endpoints de monitoramento/infra que nao devem poluir o log de auditoria.
     */
    private function isMonitoringEndpoint(Request $request): bool
    {
        return $request->is('api/health', 'api/health/*', 'api/metrics', 'health', 'metrics');
    }
}
