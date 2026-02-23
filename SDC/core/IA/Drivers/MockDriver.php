<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Illuminate\Support\Str;
use Generator;

class MockDriver implements LLMDriverInterface
{
    public function chat(array $messages, array $options = []): string
    {
        $lastMessage = end($messages)['content'] ?? '';
        return $this->generateMockResponse($lastMessage);
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        $lastMessage = end($messages)['content'] ?? '';
        $response = $this->generateMockResponse($lastMessage);
        
        // Simulate token streaming
        $tokens = preg_split('/( )/', $response, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        foreach ($tokens as $token) {
            usleep(50000); // 50ms delay for realism
            yield $token;
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        // Mock tool usage not implemented for simplicity
        return [
            'content' => $this->chat($messages, $options),
            'tool_calls' => []
        ];
    }

    protected function generateMockResponse(string $input): string
    {
        $input = strtolower($input);

        if (str_contains($input, 'protocolo') || str_contains($input, '9928')) {
            return "📋 **Análise do Protocolo #9928**\n\n" .
                   "**Status:** 🔴 Pendente de Vistoria\n" .
                   "**Local:** Venda Nova, Belo Horizonte - MG\n" .
                   "**Risco Identificado:** Deslizamento de encosta (Grau 4)\n\n" .
                   "O sistema identificou que a última vistoria técnica venceu há 48 horas. Recomendo despachar a equipe Alpha-02 imediatamente para o local, dado o alerta meteorológico de chuvas intensas para as próximas 6 horas.\n\n" .
                   "Deseja gerar a ordem de serviço agora?";
        }

        if (str_contains($input, 'alerta') || str_contains($input, 'chuva') || str_contains($input, 'tempo')) {
            return "⛈️ **Alerta Meteorológico Vigente**\n\n" .
                   "**Região:** Metropolitana de Belo Horizonte\n" .
                   "**Previsão:** Chuvas intensas (50-100mm) nas próximas 24h.\n" .
                   "**Nível de Alerta:** Laranja (Perigo)\n\n" .
                   "As estações de monitoramento na Pampulha e Venda Nova já registram acúmulo de 30mm na última hora. O nível do Ribeirão Arrudas está em 75% da calha.\n\n" .
                   "Ação sugerida: Emitir alerta SMS para as comunidades ribeirinhas cadastrados.";
        }

        if (str_contains($input, 'abrigo') || str_contains($input, 'vaga')) {
            return "🏠 **Gestão de Abrigos**\n\n" .
                   "Identifiquei 3 abrigos ativos na região solicitada:\n\n" .
                   "1. **Escola Municipal Santos Dumont** (Venda Nova)\n   - Capacidade: 150\n   - Ocupação: 45% (68 pessoas)\n   - Status: Aberto\n\n" .
                   "2. **Ginásio Poliesportivo Barreiro**\n   - Capacidade: 300\n   - Ocupação: 10% (30 pessoas)\n   - Status: Aberto\n\n" .
                   "3. **Centro Comunitário Norte**\n   - Status: Fechado para manutenção\n\n" .
                   "Posso iniciar o processo de triagem para novos desabrigados?";
        }

        return "🤖 **Assistente Virtual NewSDC**\n\n" .
               "Recebi sua mensagem: \"$input\".\n\n" .
               "Como sou uma versão de demonstração (Mock Driver), posso simular respostas para:\n" .
               "- Consultas de Protocolos (ex: #9928)\n" .
               "- Alertas Meteorológicos\n" .
               "- Gestão de Abrigos\n\n" .
               "Por favor, tente perguntar sobre um desses tópicos para ver a mágica acontecer!";
    }

    public function getDriverName(): string
    {
        return 'mock';
    }

    public function getModel(): string
    {
        return 'demo-v1';
    }
}
