<?php

namespace App\Http\Middleware;

use App\Jobs\RecordActivityLog;
use App\Services\Logging\ActivityLogger;
use App\Support\Logging\AmostragemDeAuditoria;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Logging automático de requisições API
 * Sistema Crítico 24/7 - Auditoria Completa
 *
 * E o registrador de auditoria das rotas de API: marca a requisicao para que o
 * LogSystemActivity (que roda no mesmo grupo) nao registre o mesmo evento uma
 * segunda vez. Antes desta marca, toda requisicao de API despachava DOIS jobs
 * de auditoria -- o dobro de trabalho para a fila, com dois registros do mesmo
 * fato e campos diferentes em cada um.
 */
class LogApiRequests
{
    /** Marca consumida pelo LogSystemActivity para nao duplicar o registro. */
    public const ATRIBUTO_AUDITADO = 'auditoria.registrada_por_api';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // A marca e posta na ida, e nao na volta: o LogSystemActivity esta mais
        // INTERNO nesta pilha, entao o pos-processamento dele roda ANTES do
        // nosso. Marcar depois do $next chegaria tarde demais.
        $request->attributes->set(self::ATRIBUTO_AUDITADO, true);

        // Executa a requisição
        $response = $next($request);

        // Calcula duração
        $duration = (microtime(true) - $startTime) * 1000; // em ms

        $status = $response->getStatusCode();

        // Log detalhado da requisição (ver AmostragemDeAuditoria: erros e
        // mutacoes sempre entram; so leitura bem-sucedida e amostrada).
        // Despacha pra fila: o ActivityLogger (debug_backtrace + Redis + arquivo)
        // sai do hot path; aqui so monta o array e da um push.
        if (AmostragemDeAuditoria::deveRegistrar($request, $status)) {
            $userId = auth()->id();
            RecordActivityLog::dispatch(
                'api',
                'request',
                [
                    'endpoint' => $request->path(),
                    'status_code' => $status,
                    'duration_ms' => $duration,
                    'user_id' => $userId,
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'request_id' => $request->header('X-Request-ID') ?? uniqid(),
                    'query_params' => $request->query(),
                    // Pelo cabecalho, nao por getContent(): ler o corpo so para
                    // saber se ele existe materializa o payload inteiro no worker.
                    'has_body' => ((int) $request->headers->get('Content-Length', '0')) > 0,
                    'response_size' => $this->tamanhoDaResposta($response),
                ],
                $userId ? (string) $userId : null,
                $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info'),
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
     * Tamanho da resposta sem materializa-la.
     *
     * getContent() numa StreamedResponse dispara o callback (ou devolve false) e
     * numa BinaryFileResponse carrega o arquivo inteiro na memoria do worker --
     * um export de alguns MB era copiado so para medir o proprio tamanho. Quando
     * o tamanho nao e conhecido de antemao, e melhor nao reportar do que pagar
     * por ele.
     */
    private function tamanhoDaResposta(Response $response): ?int
    {
        if ($response instanceof StreamedResponse) {
            return null;
        }

        if ($response instanceof BinaryFileResponse) {
            return $response->getFile()->getSize() ?: null;
        }

        $conteudo = $response->getContent();

        return $conteudo === false ? null : strlen($conteudo);
    }
}
