<?php

namespace App\Http\Middleware;

use App\Jobs\RecordActivityLog;
use App\Support\Logging\AmostragemDeAuditoria;
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
        $statusCode = $response->getStatusCode();

        // O LogApiRequests ja registrou esta requisicao, com campos mais ricos
        // (endpoint, user agent, query params, tamanho da resposta). Registrar de
        // novo aqui era o dobro de jobs de auditoria por requisicao de API e dois
        // registros do mesmo fato na fila.
        if ($request->attributes->get(LogApiRequests::ATRIBUTO_AUDITADO) === true) {
            return $response;
        }

        // Amostragem: ver AmostragemDeAuditoria. Erros e mutacoes sao sempre
        // registrados; so leitura bem-sucedida respeita o percentual.
        if (! AmostragemDeAuditoria::deveRegistrar($request, $statusCode)) {
            return $response;
        }

        RecordActivityLog::dispatch(
            'system_activity',
            $type,
            [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'route' => $request->route()?->getName(),
                'status_code' => $statusCode,
                'duration_ms' => round($duration, 2),
                'ip' => $request->ip(),
                'user_id' => $userId ?? 'guest',
            ],
            $userId ? (string) $userId : null,
            $statusCode >= 400 ? 'warning' : 'info',
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

            // Polling do sininho: o cliente bate a cada 30s por usuario e a
            // resposta costuma ser 304. Auditar isso gera uma ESCRITA por poll
            // sem sinal nenhum ("usuario abriu o inbox" nao e evento), pelo
            // mesmo motivo de health/metrics. As mutacoes (POST /lidas etc.)
            // continuam logadas: sao acoes de clique.
            'notificacoes/inbox',

            // Sem barra inicial, como diz o comentario acima: com ela estes
            // padroes nunca casavam e o debugbar/log-viewer vinha sendo
            // auditado ao contrario da intencao.
            '_debugbar/*',
            'log-viewer*',
            'logs*', // Não logar o próprio visualizador de logs
            '_ignition/*',
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
