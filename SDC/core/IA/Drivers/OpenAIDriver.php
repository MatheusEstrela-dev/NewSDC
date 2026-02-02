<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAIDriver implements LLMDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $maxTokens;
    protected float $temperature;

    public function __construct()
    {
        $config = config('ai.drivers.openai');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gpt-4o';
        $this->baseUrl = $config['base_url'] ?? 'https://api.openai.com/v1';
        $this->maxTokens = $config['max_tokens'] ?? 4096;
        $this->temperature = $config['temperature'] ?? 0.7;

        if (empty($this->apiKey)) {
            throw new RuntimeException('OpenAI API key not configured');
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'temperature' => $options['temperature'] ?? $this->temperature,
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI API Error', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'] ?? '';
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->withOptions(['stream' => true])->timeout(120)->post($this->baseUrl . '/chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'stream' => true,
        ]);

        $body = $response->getBody();
        while (!$body->eof()) {
            $line = $body->read(1024);
            foreach (explode("\n", $line) as $l) {
                if (str_starts_with($l, 'data: ')) {
                    $json = substr($l, 6);
                    if ($json === '[DONE]') return;
                    $data = json_decode($json, true);
                    if (isset($data['choices'][0]['delta']['content'])) {
                        yield $data['choices'][0]['delta']['content'];
                    }
                }
            }
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        $formattedTools = array_map(fn($tool) => [
            'type' => 'function',
            'function' => $tool->toFunctionDefinition(),
        ], $tools);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl . '/chat/completions', [
            'model' => $options['model'] ?? $this->model,
            'messages' => $messages,
            'max_tokens' => $options['max_tokens'] ?? $this->maxTokens,
            'tools' => $formattedTools,
            'tool_choice' => $options['tool_choice'] ?? 'auto',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI API error: ' . $response->body());
        }

        $data = $response->json();
        $message = $data['choices'][0]['message'] ?? [];

        return [
            'content' => $message['content'] ?? null,
            'tool_calls' => $message['tool_calls'] ?? [],
            'finish_reason' => $data['choices'][0]['finish_reason'] ?? null,
            'usage' => $data['usage'] ?? [],
        ];
    }

    public function embedding(string $text): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->timeout(30)->post($this->baseUrl . '/embeddings', [
            'model' => 'text-embedding-3-small',
            'input' => $text,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI Embedding error: ' . $response->body());
        }

        return $response->json()['data'][0]['embedding'] ?? [];
    }

    public function getDriverName(): string
    {
        return 'openai';
    }

    public function getModel(): string
    {
        return $this->model;
    }
}
