<?php

namespace App\Core\IA\Plugins\Communication;

use App\Core\IA\Contracts\AIPluginInterface;
use Illuminate\Support\Facades\Log;

class WhatsAppPlugin implements AIPluginInterface
{
    public function getName(): string
    {
        return "enviar_whatsapp";
    }

    public function getDescription(): string
    {
        return "Envia uma mensagem de texto para um número de WhatsApp.";
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'telefone' => [
                    'type' => 'string',
                    'description' => 'Número do telefone com DDD e código do país (apenas números). Ex: 5531999999999',
                ],
                'mensagem' => [
                    'type' => 'string',
                    'description' => 'O conteúdo da mensagem de texto a ser enviada.',
                ],
            ],
            'required' => ['telefone', 'mensagem'],
        ];
    }

    public function execute(array $params)
    {
        $phone = $params['telefone'] ?? null;
        $message = $params['mensagem'] ?? null;

        if (!$phone || !$message) {
            throw new \InvalidArgumentException("Telefone e mensagem são obrigatórios.");
        }

        // Simulação de envio (Aqui entraria a chamada para Evolution API, Twilio ou Meta)
        // Em produção: Http::post('https://api.whatsapp-provider.com/send', [...])
        
        Log::info("IA WhatsApp Output: Enviando para {$phone}: {$message}");

        // Retornamos um recibo de sucesso para a IA saber que o comando foi processado
        return [
            'status' => 'success',
            'channel' => 'whatsapp',
            'recipient' => $phone,
            'content' => $message,
            'timestamp' => now()->toIso8601String()
        ];
    }
}
