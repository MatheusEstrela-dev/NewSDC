<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de Idempotência
 *
 * Garante que requisições duplicadas (ex: cliente clicou 2x em "Enviar")
 * não sejam processadas múltiplas vezes.
 *
 * Baseado no papiro.md:
 * - Cliente envia header `Idempotency-Key` com UUID v4
 * - Se a key já existe no Redis, retorna a resposta cacheada
 * - Se não existe, processa e cacheia a resposta (TTL 24h)
 *
 * Crítico para APIs financeiras, estoque, pagamentos, etc.
 */
class IdempotencyMiddleware
{
    /**
     * Métodos HTTP que suportam idempotência
     */
    private const IDEMPOTENT_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * TTL do cache de idempotência (24 horas)
     */
    private const CACHE_TTL = 86400;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Apenas aplica idempotência para métodos não-safe
        if (!in_array($request->method(), self::IDEMPOTENT_METHODS)) {
            return $next($request);
        }

        // Pega a Idempotency-Key do header
        $idempotencyKey = $request->header('Idempotency-Key');

        // Se não tem key, processa normalmente (sem idempotência)
        if (!$idempotencyKey) {
            return $next($request);
        }

        // Valida o formato da key (deve ser UUID v4)
        if (!$this->isValidIdempotencyKey($idempotencyKey)) {
            return response()->json([
                'error' => 'Invalid Idempotency Key',
                'message' => 'Idempotency-Key must be a valid UUID v4',
                'example' => '123e4567-e89b-12d3-a456-426614174000',
            ], 400);
        }

        // Monta a cache key (inclui user_id para isolar por usuário)
        $userId = auth()->id() ?? 'anonymous';
        $cacheKey = "idempotency:{$userId}:{$idempotencyKey}";
        $lockKey = "idempotency_lock:{$userId}:{$idempotencyKey}";

        // Verifica se já existe resposta cacheada (primeira verificação)
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            // Log para auditoria
            Log::info('Idempotent request detected, returning cached response', [
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
                'path' => $request->path(),
                'method' => $request->method(),
            ]);

            // Retorna a resposta cacheada
            return $this->reconstructResponse($cachedResponse);
        }

        // Tenta adquirir lock para evitar race condition
        // Se duas requisições idênticas chegarem ao mesmo tempo,
        // apenas uma vai processar, a outra vai aguardar
        $lock = Cache::lock($lockKey, 10); // 10 segundos

        if ($lock->get()) {
            try {
                // Double-check locking pattern
                // Verifica de novo se foi cacheado enquanto esperava o lock
                $cachedResponse = Cache::get($cacheKey);

                if ($cachedResponse !== null) {
                    Log::info('Idempotent request detected after lock acquisition', [
                        'idempotency_key' => $idempotencyKey,
                        'user_id' => $userId,
                    ]);

                    return $this->reconstructResponse($cachedResponse);
                }

                // Processa a requisição normalmente
                $response = $next($request);

                // Cacheia a resposta apenas se foi bem-sucedida (2xx ou 3xx)
                $statusCode = $response->getStatusCode();
                if ($statusCode >= 200 && $statusCode < 400) {
                    $this->cacheResponse($cacheKey, $response);
                }

                // Adiciona header informativo
                $response->headers->set('X-Idempotency-Key', $idempotencyKey);
                $response->headers->set('X-Idempotency-Cached', 'false');

                return $response;

            } finally {
                // Sempre libera o lock
                $lock->release();
            }
        }

        // Se não conseguiu o lock, aguarda um pouco e retorna a resposta cacheada
        // (provavelmente outra thread está processando)
        usleep(100000); // 100ms

        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            Log::info('Idempotent request served from cache after lock timeout', [
                'idempotency_key' => $idempotencyKey,
                'user_id' => $userId,
            ]);

            return $this->reconstructResponse($cachedResponse);
        }

        // Fallback: se ainda não tem cache, processa normalmente
        // Não é ideal, mas melhor que bloquear a requisição
        Log::warning('Idempotency lock timeout, processing anyway', [
            'idempotency_key' => $idempotencyKey,
            'user_id' => $userId,
        ]);

        $response = $next($request);
        $response->headers->set('X-Idempotency-Key', $idempotencyKey);
        $response->headers->set('X-Idempotency-Cached', 'false');

        return $response;
    }

    /**
     * Valida se a Idempotency-Key é um UUID v4 válido
     */
    private function isValidIdempotencyKey(string $key): bool
    {
        // UUID v4 format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        return preg_match($pattern, $key) === 1;
    }

    /**
     * Cacheia a resposta no Redis/Cache
     */
    private function cacheResponse(string $cacheKey, Response $response): void
    {
        try {
            $cachedData = [
                'status_code' => $response->getStatusCode(),
                'headers' => $response->headers->all(),
                'content' => $response->getContent(),
                'cached_at' => now()->toIso8601String(),
            ];

            Cache::put($cacheKey, $cachedData, self::CACHE_TTL);

        } catch (\Exception $e) {
            // Se falhar ao cachear, apenas loga (não quebra a requisição)
            Log::error('Failed to cache idempotent response', [
                'error' => $e->getMessage(),
                'key' => $cacheKey,
            ]);
        }
    }

    /**
     * Reconstrói a Response a partir dos dados cacheados
     */
    private function reconstructResponse(array $cachedData): Response
    {
        $response = response($cachedData['content'], $cachedData['status_code']);

        // Restaura headers (exceto alguns que não devem ser cacheados)
        foreach ($cachedData['headers'] as $header => $values) {
            // Skip headers que não devem ser cacheados
            if (in_array(strtolower($header), ['set-cookie', 'date'])) {
                continue;
            }

            $response->headers->set($header, $values);
        }

        // Adiciona headers informativos
        $response->headers->set('X-Idempotency-Cached', 'true');
        $response->headers->set('X-Idempotency-Cached-At', $cachedData['cached_at']);

        return $response;
    }
}
