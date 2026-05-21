<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Integrations\BaseConnector;
use App\Integrations\Requests\GetTokenRequest;

/**
 * Serviço para gerenciar tokens de múltiplas APIs
 * 
 * Este serviço centraliza a obtenção e cache de tokens
 * para todas as APIs externas integradas.
 */
class IntegrationTokenService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('integrations');
    }

    /**
     * Obtém um token para uma API específica
     * 
     * @param string $apiKey Chave da API (pae, rat, tdap, bi)
     * @param bool $forceRefresh Força a renovação do token mesmo se estiver em cache
     * @return string|null Token de autenticação ou null em caso de erro
     */
    public function getToken(string $apiKey, bool $forceRefresh = false): ?string
    {
        $apiConfig = $this->config['apis'][$apiKey] ?? null;

        if (!$apiConfig) {
            Log::error("API não encontrada: {$apiKey}");
            return null;
        }

        // Verifica cache se não for refresh forçado
        if (!$forceRefresh && $this->config['token_cache']['enabled'] ?? true) {
            $cachedToken = Cache::get($this->getCacheKey($apiKey));
            if ($cachedToken) {
                return $cachedToken;
            }
        }

        try {
            // Obtém novo token
            $token = $this->requestToken($apiKey, $apiConfig);

            if ($token) {
                // Armazena em cache
                $ttl = $this->config['token_cache']['ttl'] ?? 3300;
                Cache::put($this->getCacheKey($apiKey), $token, $ttl);

                Log::info("Token obtido com sucesso para API: {$apiKey}");
                return $token;
            }
        } catch (\Exception $e) {
            Log::error("Erro ao obter token para API {$apiKey}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Obtém tokens para múltiplas APIs de uma vez
     * 
     * @param array $apiKeys Array de chaves de APIs
     * @return array Array associativo com apiKey => token
     */
    public function getMultipleTokens(array $apiKeys): array
    {
        $tokens = [];

        foreach ($apiKeys as $apiKey) {
            $tokens[$apiKey] = $this->getToken($apiKey);
        }

        return $tokens;
    }

    /**
     * Gera um token único para Power BI que permite acesso a múltiplas APIs.
     *
     * Vincula o token ao user dono (o que esta gerando via API). Quando esse
     * token eh apresentado via X-PowerBI-Token, o DecretacoesApiAuth resolve
     * o user vinculado e isola recursos (especialmente RequestTrace) por
     * dono, evitando vazamento entre integracoes diferentes.
     *
     * @param array|null $allowedApis APIs permitidas (null = todas configuradas)
     * @param \App\Models\User|null $owner User dono do token (default: request()->user())
     * @return array Array com token único e informações das APIs
     */
    public function generatePowerBIToken(?array $allowedApis = null, ?\App\Models\User $owner = null): array
    {
        $allowedApis = $allowedApis ?? $this->config['power_bi']['allowed_apis'] ?? [];

        // Obtém tokens para todas as APIs permitidas
        $apiTokens = [];
        foreach ($allowedApis as $apiKey) {
            $token = $this->getToken($apiKey);
            if ($token) {
                $apiConfig = $this->config['apis'][$apiKey] ?? [];
                $apiTokens[$apiKey] = [
                    'token' => $token,
                    'base_url' => $apiConfig['base_url'] ?? '',
                    'name' => $apiConfig['name'] ?? $apiKey,
                ];
            }
        }

        // Gera um token único para Power BI
        $powerBIToken = $this->generateUniqueToken($apiTokens);

        // Armazena o mapeamento do token com user_id (formato novo).
        // validatePowerBIToken aceita tanto formato novo {user_id, apis}
        // quanto formato legado (apiTokens direto) para compatibilidade
        // com tokens emitidos antes desta mudanca.
        $ttl = $this->config['power_bi']['token_ttl'] ?? 3600;
        Cache::put($this->getPowerBICacheKey($powerBIToken), [
            'user_id' => $owner?->id,
            'apis' => $apiTokens,
        ], $ttl);

        return [
            'token' => $powerBIToken,
            'expires_in' => $ttl,
            'apis' => array_keys($apiTokens),
            'endpoints' => $this->getPowerBIEndpoints($apiTokens),
        ];
    }

    /**
     * Valida e retorna o payload do token Power BI.
     *
     * Sempre retorna no formato {user_id, apis} para callers; payload legado
     * em cache (apenas $apiTokens direto) eh normalizado para user_id=null.
     *
     * @param string $powerBIToken Token do Power BI
     * @return array{user_id: int|null, apis: array}|null null se invalido/expirado
     */
    public function validatePowerBIToken(string $powerBIToken): ?array
    {
        $cached = Cache::get($this->getPowerBICacheKey($powerBIToken));

        if ($cached === null) {
            return null;
        }

        // Formato novo (com user_id): retorna direto
        if (is_array($cached) && array_key_exists('user_id', $cached) && array_key_exists('apis', $cached)) {
            return $cached;
        }

        // Formato legado (apiTokens direto): normaliza com user_id null
        return [
            'user_id' => null,
            'apis' => is_array($cached) ? $cached : [],
        ];
    }

    /**
     * Faz requisição para obter token de uma API
     */
    protected function requestToken(string $apiKey, array $apiConfig): ?string
    {
        $endpoint = $apiConfig['base_url'] . ($apiConfig['token_endpoint'] ?? '/api/auth/token');
        $credentials = $apiConfig['credentials'] ?? [];
        $scopes = $apiConfig['scopes'] ?? [];

        $response = Http::asForm()->post($endpoint, [
            'grant_type' => 'client_credentials',
            'client_id' => $credentials['client_id'] ?? '',
            'client_secret' => $credentials['client_secret'] ?? '',
            'scope' => implode(' ', $scopes),
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return $data['access_token'] ?? $data['token'] ?? null;
        }

        Log::error("Falha ao obter token para {$apiKey}", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return null;
    }

    /**
     * Gera um token único para Power BI
     */
    protected function generateUniqueToken(array $apiTokens): string
    {
        $payload = [
            'apis' => array_keys($apiTokens),
            'timestamp' => now()->timestamp,
            'random' => bin2hex(random_bytes(16)),
        ];

        return hash('sha256', json_encode($payload));
    }

    /**
     * Retorna os endpoints formatados para Power BI
     */
    protected function getPowerBIEndpoints(array $apiTokens): array
    {
        $endpoints = [];

        foreach ($apiTokens as $apiKey => $data) {
            $endpoints[$apiKey] = [
                'url' => $data['base_url'],
                'name' => $data['name'],
            ];
        }

        return $endpoints;
    }

    /**
     * Retorna a chave de cache para um token de API
     */
    protected function getCacheKey(string $apiKey): string
    {
        $prefix = $this->config['token_cache']['prefix'] ?? 'api_token_';
        return $prefix . $apiKey;
    }

    /**
     * Retorna a chave de cache para um token do Power BI
     */
    protected function getPowerBICacheKey(string $token): string
    {
        return 'power_bi_token_' . $token;
    }

    /**
     * Limpa o cache de tokens de uma API específica
     */
    public function clearTokenCache(string $apiKey): void
    {
        Cache::forget($this->getCacheKey($apiKey));
    }

    /**
     * Limpa todos os caches de tokens
     */
    public function clearAllTokenCache(): void
    {
        foreach (array_keys($this->config['apis'] ?? []) as $apiKey) {
            $this->clearTokenCache($apiKey);
        }
    }
}

