<?php

namespace App\Services\Webhook;

use Illuminate\Support\Facades\Log;

/**
 * Validador de assinaturas HMAC para webhooks
 * Garante que o payload veio de uma fonte confiavel
 */
class WebhookSignatureValidator
{
    /**
     * Valida a assinatura de um webhook
     *
     * @param string $payload Payload raw do webhook
     * @param string $signature Assinatura recebida no header
     * @param string $provider Nome do provider (github, stripe, default)
     * @return bool
     */
    public function validate(string $payload, string $signature, string $provider = 'default'): bool
    {
        $secret = $this->getSecretForProvider($provider);

        if (!$secret) {
            Log::channel('webhooks')->warning('No secret configured for provider', [
                'provider' => $provider,
            ]);
            return false;
        }

        $expectedSignature = $this->computeSignature($payload, $secret, $provider);

        $isValid = hash_equals($expectedSignature, $signature);

        if (!$isValid) {
            Log::channel('webhooks')->warning('Invalid webhook signature', [
                'provider' => $provider,
                'expected_prefix' => substr($expectedSignature, 0, 20) . '...',
                'received_prefix' => substr($signature, 0, 20) . '...',
            ]);
        }

        return $isValid;
    }

    /**
     * Computa a assinatura esperada baseado no algoritmo do provider
     */
    protected function computeSignature(string $payload, string $secret, string $provider): string
    {
        $algorithm = $this->getAlgorithmForProvider($provider);

        return match ($algorithm) {
            'sha256' => 'sha256=' . hash_hmac('sha256', $payload, $secret),
            'sha1' => 'sha1=' . hash_hmac('sha1', $payload, $secret),
            'sha512' => 'sha512=' . hash_hmac('sha512', $payload, $secret),
            default => hash_hmac('sha256', $payload, $secret),
        };
    }

    /**
     * Obtem o secret configurado para o provider
     */
    protected function getSecretForProvider(string $provider): ?string
    {
        return config("webhooks.providers.{$provider}.secret")
            ?? config('webhooks.providers.default.secret');
    }

    /**
     * Obtem o algoritmo de hash para o provider
     */
    protected function getAlgorithmForProvider(string $provider): string
    {
        return config("webhooks.providers.{$provider}.algorithm", 'sha256');
    }

    /**
     * Extrai a assinatura do header conforme o provider
     *
     * @param array $headers Headers da requisicao
     * @param string $provider Nome do provider
     * @return string|null
     */
    public function extractSignatureFromHeaders(array $headers, string $provider): ?string
    {
        $headerName = $this->getSignatureHeaderForProvider($provider);

        // Headers podem vir em diferentes formatos (case-insensitive)
        foreach ($headers as $key => $value) {
            if (strtolower($key) === strtolower($headerName)) {
                return is_array($value) ? $value[0] : $value;
            }
        }

        return null;
    }

    /**
     * Obtem o nome do header de assinatura para o provider
     */
    protected function getSignatureHeaderForProvider(string $provider): string
    {
        return match ($provider) {
            'github' => 'X-Hub-Signature-256',
            'stripe' => 'Stripe-Signature',
            'gitlab' => 'X-Gitlab-Token',
            default => 'X-Webhook-Signature',
        };
    }

    /**
     * Gera uma assinatura para envio de webhook (outbound)
     *
     * @param string $payload Payload a ser assinado
     * @param string|null $secret Secret (usa default se nao fornecido)
     * @return string
     */
    public function generateSignature(string $payload, ?string $secret = null): string
    {
        $secret = $secret ?? config('webhooks.providers.default.secret');
        return 'sha256=' . hash_hmac('sha256', $payload, $secret);
    }
}
