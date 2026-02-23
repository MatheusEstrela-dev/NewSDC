<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeDriver implements LLMDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $maxTokens;

    public function __construct()
    {
        $config = config('ai.drivers.claude');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'claude-3-5-sonnet-20241022';
        $this->baseUrl = $config['base_url'] ?? 'https://api.anthropic.com/v1';
        $this->maxTokens = $config['max_tokens'] ?? 4096;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Anthropic API key not configured');
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        [$systemMessage, $filteredMessages] = $this->separateSystemMessage($messages);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $filteredMessages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
        ];

        if ($systemMessage) $payload['system'] = $systemMessage;

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl . '/messages', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->body());
        }

        return $response->json()['content'][0]['text'] ?? '';
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        [$systemMessage, $filteredMessages] = $this->separateSystemMessage($messages);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $filteredMessages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'stream' => true,
        ];

        if ($systemMessage) $payload['system'] = $systemMessage;

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->withOptions(['stream' => true])->timeout(120)->post($this->baseUrl . '/messages', $payload);

        $body = $response->getBody();
        while (!$body->eof()) {
            foreach (explode("\n", $body->read(1024)) as $l) {
                if (str_starts_with($l, 'data: ')) {
                    $data = json_decode(substr($l, 6), true);
                    if (isset($data['delta']['text'])) yield $data['delta']['text'];
                    if (($data['type'] ?? '') === 'message_stop') return;
                }
            }
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        [$systemMessage, $filteredMessages] = $this->separateSystemMessage($messages);

        $formattedTools = array_map(fn($tool) => [
            'name' => $tool->getName(),
            'description' => $tool->getDescription(),
            'input_schema' => $tool->getParametersSchema(),
        ], $tools);

        $payload = [
            'model' => $options['model'] ?? $this->model,
            'messages' => $filteredMessages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'tools' => $formattedTools,
        ];

        if ($systemMessage) $payload['system'] = $systemMessage;

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(60)->post($this->baseUrl . '/messages', $payload);

        if (!$response->successful()) {
            throw new RuntimeException('Claude API error: ' . $response->body());
        }

        $data = $response->json();
        $toolCalls = [];
        $content = null;

        foreach ($data['content'] ?? [] as $block) {
            if ($block['type'] === 'text') $content = $block['text'];
            elseif ($block['type'] === 'tool_use') {
                $toolCalls[] = [
                    'id' => $block['id'],
                    'type' => 'function',
                    'function' => ['name' => $block['name'], 'arguments' => json_encode($block['input'])],
                ];
            }
        }

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'finish_reason' => $data['stop_reason'] ?? null,
            'usage' => $data['usage'] ?? [],
        ];
    }

    public function embedding(string $text): array
    {
        throw new RuntimeException('Claude does not support embeddings. Use OpenAI.');
    }

    public function getDriverName(): string
    {
        return 'claude';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    protected function separateSystemMessage(array $messages): array
    {
        $system = null;
        $filtered = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') $system = $msg['content'];
            else $filtered[] = $msg;
        }
        return [$system, $filtered];
    }
}
