<?php

namespace App\Services\Integration;

use App\Jobs\ProcessIntegration;
use App\Models\Integration;
use App\Enums\RequestPriority;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Hub centralizado para gerenciar integrações bidirecionais
 * Suporta múltiplos tipos: REST, SOAP, GraphQL, WebHooks, Database, File Transfer
 */
class IntegrationHubService
{
    /**
     * Enfileira integração para execução assíncrona
     */
    public function queueIntegration(array $config, RequestPriority $priority, ?int $userId = null): string
    {
        $integrationId = 'int_' . Str::random(16);

        // Armazena config no cache temporariamente
        Cache::put("integration:{$integrationId}", [
            'config' => $config,
            'user_id' => $userId,
            'status' => 'pending',
            'created_at' => now(),
        ], 3600); // 1 hora

        ProcessIntegration::dispatch($integrationId, $config, $userId)
            ->onQueue($priority->queue());

        return $integrationId;
    }

    /**
     * Executa integração de forma síncrona (tempo real)
     */
    public function executeSync(array $config, ?int $userId = null): array
    {
        $startTime = microtime(true);
        $integrationId = 'int_' . Str::random(16);

        try {
            // Processa conforme tipo
            $result = match($config['integration_type']) {
                'rest_api' => $this->executeRestApi($config),
                'graphql' => $this->executeGraphQL($config),
                'soap' => $this->executeSoap($config),
                'webhook' => $this->executeWebhook($config),
                default => throw new \Exception("Integration type {$config['integration_type']} not supported"),
            };

            $duration = round((microtime(true) - $startTime) * 1000, 2);

            // Aplica mapeamento de campos se configurado
            $mappedResponse = isset($config['mapping'])
                ? $this->applyFieldMapping($result['data'], $config['mapping'])
                : $result['data'];

            // Salva log
            $this->logIntegration($integrationId, $config, $result, $duration, true, $userId);

            return [
                'integration_id' => $integrationId,
                'sent_data' => $config['payload'],
                'received_data' => $result['data'],
                'mapped_response' => $mappedResponse,
                'status' => $result['status'],
                'duration_ms' => $duration,
            ];

        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->logIntegration($integrationId, $config, ['error' => $e->getMessage()], $duration, false, $userId);

            throw $e;
        }
    }

    /**
     * Executa integração REST API
     */
    private function executeRestApi(array $config): array
    {
        $http = Http::timeout($config['timeout'] ?? 30);

        // Aplica autenticação
        if (isset($config['auth'])) {
            $http = $this->applyAuth($http, $config['auth']);
        }

        // Aplica headers customizados
        if (isset($config['headers'])) {
            $http = $http->withHeaders($config['headers']);
        }

        $method = strtolower($config['method']);
        $response = $http->$method($config['endpoint'], $config['payload']);

        return [
            'status' => $response->status(),
            'data' => $response->json() ?? $response->body(),
            'headers' => $response->headers(),
        ];
    }

    /**
     * Executa integração GraphQL
     */
    private function executeGraphQL(array $config): array
    {
        $query = $config['payload']['query'] ?? '';
        $variables = $config['payload']['variables'] ?? [];

        $response = Http::post($config['endpoint'], [
            'query' => $query,
            'variables' => $variables,
        ]);

        return [
            'status' => $response->status(),
            'data' => $response->json(),
            'headers' => $response->headers(),
        ];
    }

    /**
     * Executa integração SOAP
     */
    private function executeSoap(array $config): array
    {
        try {
            $client = new \SoapClient($config['endpoint'], [
                'trace' => 1,
                'exceptions' => true,
            ]);

            $result = $client->__soapCall($config['action'], [$config['payload']]);

            return [
                'status' => 200,
                'data' => json_decode(json_encode($result), true),
                'headers' => [],
            ];

        } catch (\SoapFault $e) {
            throw new \Exception("SOAP Error: {$e->getMessage()}");
        }
    }

    /**
     * Executa webhook
     */
    private function executeWebhook(array $config): array
    {
        $response = Http::timeout($config['timeout'] ?? 30)
            ->withHeaders($config['headers'] ?? [])
            ->post($config['endpoint'], $config['payload']);

        // Se bidirectional, envia callback com resposta
        if (($config['bidirectional'] ?? false) && isset($config['callback_url'])) {
            Http::post($config['callback_url'], [
                'original_payload' => $config['payload'],
                'response' => $response->json(),
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return [
            'status' => $response->status(),
            'data' => $response->json() ?? $response->body(),
            'headers' => $response->headers(),
        ];
    }

    /**
     * Aplica autenticação na requisição
     */
    private function applyAuth($http, array $auth)
    {
        return match($auth['type']) {
            'bearer' => $http->withToken($auth['token']),
            'basic' => $http->withBasicAuth($auth['username'], $auth['password']),
            'api_key' => $http->withHeaders(['X-API-Key' => $auth['token']]),
            default => $http,
        };
    }

    /**
     * Aplica mapeamento de campos (input → output)
     */
    private function applyFieldMapping(array $data, array $mapping): array
    {
        $mapped = [];

        foreach ($mapping as $internalField => $externalField) {
            $mapped[$internalField] = data_get($data, $externalField);
        }

        return $mapped;
    }

    /**
     * Obtém status de integração assíncrona
     */
    public function getStatus(string $integrationId): array
    {
        $cached = Cache::get("integration:{$integrationId}");

        if (!$cached) {
            return [
                'integration_id' => $integrationId,
                'status' => 'not_found',
                'error' => 'Integration not found or expired',
            ];
        }

        return [
            'integration_id' => $integrationId,
            'status' => $cached['status'],
            'result' => $cached['result'] ?? null,
            'error' => $cached['error'] ?? null,
            'created_at' => $cached['created_at'],
            'completed_at' => $cached['completed_at'] ?? null,
        ];
    }

    /**
     * Retorna templates pré-configurados para integrações populares
     */
    public function getTemplates(): array
    {
        return [
            [
                'id' => 'salesforce_create_lead',
                'name' => 'Salesforce - Criar Lead',
                'description' => 'Cria um novo lead no Salesforce',
                'integration_type' => 'rest_api',
                'endpoint' => 'https://{{instance}}.salesforce.com/services/data/v58.0/sobjects/Lead',
                'method' => 'POST',
                'required_fields' => ['LastName', 'Company'],
                'auth' => ['type' => 'bearer'],
                'example_payload' => [
                    'LastName' => 'Silva',
                    'FirstName' => 'João',
                    'Company' => 'Empresa XPTO',
                    'Email' => 'joao@example.com',
                ],
            ],
            [
                'id' => 'sap_create_order',
                'name' => 'SAP - Criar Pedido',
                'description' => 'Cria um pedido de venda no SAP',
                'integration_type' => 'soap',
                'endpoint' => 'https://{{server}}:{{port}}/sap/bc/srt/rfc/sap/Z_CREATE_ORDER/001',
                'action' => 'CreateOrder',
                'required_fields' => ['customer_id', 'items'],
                'example_payload' => [
                    'customer_id' => '100001',
                    'items' => [
                        ['product_id' => 'P001', 'quantity' => 5],
                    ],
                ],
            ],
            [
                'id' => 'stripe_create_payment',
                'name' => 'Stripe - Criar Pagamento',
                'description' => 'Processa pagamento via Stripe',
                'integration_type' => 'rest_api',
                'endpoint' => 'https://api.stripe.com/v1/payment_intents',
                'method' => 'POST',
                'required_fields' => ['amount', 'currency'],
                'auth' => ['type' => 'bearer'],
                'example_payload' => [
                    'amount' => 2000,
                    'currency' => 'brl',
                    'payment_method_types' => ['card'],
                ],
            ],
            [
                'id' => 'hubspot_create_contact',
                'name' => 'HubSpot - Criar Contato',
                'description' => 'Cria contato no HubSpot CRM',
                'integration_type' => 'rest_api',
                'endpoint' => 'https://api.hubapi.com/contacts/v1/contact',
                'method' => 'POST',
                'required_fields' => ['email'],
                'auth' => ['type' => 'api_key'],
                'example_payload' => [
                    'properties' => [
                        ['property' => 'email', 'value' => 'joao@example.com'],
                        ['property' => 'firstname', 'value' => 'João'],
                        ['property' => 'lastname', 'value' => 'Silva'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Loga integração para auditoria
     */
    private function logIntegration(
        string $integrationId,
        array $config,
        array $result,
        float $duration,
        bool $success,
        ?int $userId
    ): void {
        try {
            Integration::create([
                'integration_id' => $integrationId,
                'type' => $config['integration_type'],
                'action' => $config['action'],
                'endpoint' => $config['endpoint'] ?? null,
                'payload' => $config['payload'],
                'response' => $result,
                'duration_ms' => $duration,
                'success' => $success,
                'user_id' => $userId,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log integration', ['error' => $e->getMessage()]);
        }
    }
}
