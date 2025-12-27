<?php

namespace App\Http\Middleware;

use App\Services\Logging\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para logging COMPLETO de requisições HTTP
 *
 * Vital para APIs e sistema 24/7
 * - Loga entrada (payload, headers)
 * - Loga saída (response, status code)
 * - Calcula tempo de resposta
 * - Rastreamento por request_id
 *
 * Baseado nas melhores práticas do "Log Perfeito"
 */
class LogHttpRequests
{
    /**
     * Lista de rotas que não devem ter payload logado (sensíveis)
     */
    private const SENSITIVE_ROUTES = [
        'login',
        'register',
        'password',
        'two-factor',
    ];

    /**
     * Campos sensíveis que devem ser mascarados nos logs
     */
    private const SENSITIVE_FIELDS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'api_key',
        'credit_card',
        'cvv',
        'ssn',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $requestId = $request->header('X-Request-ID') ?? 'unknown';

        // Log de entrada da requisição
        $this->logIncomingRequest($request, $requestId);

        // Executa a requisição
        $response = $next($request);

        // Calcula duração em milissegundos
        $duration = (microtime(true) - $startTime) * 1000;

        // Log de saída da requisição
        $this->logOutgoingResponse($request, $response, $duration, $requestId);

        // Adiciona request_id no header da resposta para rastreamento
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    /**
     * Log detalhado da requisição de entrada
     */
    private function logIncomingRequest(Request $request, string $requestId): void
    {
        $isSensitiveRoute = $this->isSensitiveRoute($request);

        $logData = [
            'direction' => 'incoming',
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),

            // Headers importantes (sem tokens sensíveis)
            'headers' => [
                'content-type' => $request->header('content-type'),
                'accept' => $request->header('accept'),
                'origin' => $request->header('origin'),
            ],

            // Query parameters
            'query_params' => $request->query(),

            // Payload (body) - mascarado se for rota sensível
            'payload' => $isSensitiveRoute
                ? ['masked' => 'Sensitive data not logged']
                : $this->maskSensitiveData($request->all()),

            'payload_size_bytes' => strlen($request->getContent()),

            // Contexto de usuário
            'user_id' => auth()->id(),
            'is_authenticated' => auth()->check(),
        ];

        Log::channel('events')->info('HTTP Request Received', $logData);
    }

    /**
     * Log detalhado da resposta de saída
     */
    private function logOutgoingResponse(
        Request $request,
        Response $response,
        float $duration,
        string $requestId
    ): void {
        $statusCode = $response->getStatusCode();
        $isSensitiveRoute = $this->isSensitiveRoute($request);

        // Determina o nível de log baseado no status code
        $level = match (true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            $statusCode >= 300 => 'info',
            default => 'info',
        };

        $logData = [
            'direction' => 'outgoing',
            'request_id' => $requestId,
            'status_code' => $statusCode,
            'status_text' => Response::$statusTexts[$statusCode] ?? 'Unknown',
            'duration_ms' => round($duration, 2),
            'response_size_bytes' => strlen($response->getContent()),

            // Resposta (apenas estrutura, não conteúdo completo em produção)
            'response_preview' => $this->getResponsePreview($response, $isSensitiveRoute),

            // Métricas de performance
            'is_slow' => $duration > 1000, // > 1 segundo
            'is_error' => $statusCode >= 400,

            // Contexto da requisição original
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => auth()->id(),
        ];

        // Log usando o ActivityLogger
        ActivityLogger::logApiRequest(
            endpoint: $request->path(),
            statusCode: $statusCode,
            duration: $duration,
            userId: auth()->id(),
            extra: $logData
        );

        // Log adicional para erros
        if ($statusCode >= 500) {
            Log::channel('critical')->error('HTTP 5xx Error', $logData);
        }

        // Log adicional para requests muito lentas
        if ($duration > 2000) { // > 2 segundos
            ActivityLogger::logPerformance(
                operation: 'http_slow_request',
                duration: $duration,
                metrics: [
                    'path' => $request->path(),
                    'method' => $request->method(),
                    'status_code' => $statusCode,
                    'threshold_exceeded_by_ms' => round($duration - 2000, 2),
                ]
            );
        }
    }

    /**
     * Verifica se é uma rota sensível
     */
    private function isSensitiveRoute(Request $request): bool
    {
        $path = $request->path();

        foreach (self::SENSITIVE_ROUTES as $route) {
            if (str_contains($path, $route)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mascara dados sensíveis do payload
     */
    private function maskSensitiveData(array $data): array
    {
        $masked = [];

        foreach ($data as $key => $value) {
            // Se o campo está na lista de sensíveis, mascara
            if (in_array(strtolower($key), self::SENSITIVE_FIELDS)) {
                $masked[$key] = '***MASKED***';
            }
            // Se for array, processa recursivamente
            elseif (is_array($value)) {
                $masked[$key] = $this->maskSensitiveData($value);
            }
            // Senão, mantém o valor
            else {
                $masked[$key] = $value;
            }
        }

        return $masked;
    }

    /**
     * Obtém preview da resposta (não loga tudo para não encher os logs)
     */
    private function getResponsePreview(Response $response, bool $isSensitive): array
    {
        if ($isSensitive) {
            return ['masked' => 'Sensitive response not logged'];
        }

        $content = $response->getContent();

        // Se for JSON, decodifica e pega apenas estrutura
        if ($this->isJsonResponse($response)) {
            $json = json_decode($content, true);

            if ($json === null) {
                return ['type' => 'json', 'error' => 'Invalid JSON'];
            }

            // Em produção, não loga o conteúdo completo, apenas a estrutura
            if (config('app.env') === 'production') {
                return [
                    'type' => 'json',
                    'keys' => array_keys($json),
                    'item_count' => is_array($json) ? count($json) : null,
                ];
            }

            // Em desenvolvimento, loga até 1000 caracteres
            return [
                'type' => 'json',
                'preview' => substr(json_encode($json), 0, 1000),
            ];
        }

        // Para outros tipos, apenas informa o tipo
        return [
            'type' => $response->headers->get('content-type') ?? 'unknown',
            'size_bytes' => strlen($content),
        ];
    }

    /**
     * Verifica se a resposta é JSON
     */
    private function isJsonResponse(Response $response): bool
    {
        $contentType = $response->headers->get('content-type');
        return $contentType && str_contains($contentType, 'application/json');
    }
}
