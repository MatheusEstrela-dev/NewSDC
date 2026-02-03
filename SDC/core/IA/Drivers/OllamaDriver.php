<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OllamaDriver implements LLMDriverInterface
{
    protected string $host;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $config = config('ai.drivers.ollama');
        $this->host = rtrim($config['host'] ?? 'http://localhost:11434', '/');
        $this->model = $config['model'] ?? 'llama3';
        $this->timeout = $config['timeout'] ?? 120;
    }

    public function chat(array $messages, array $options = []): string
    {
        $model = $options['model'] ?? $this->model;

        $response = Http::timeout($this->timeout)->post($this->host . '/api/chat', [
            'model' => $model,
            'messages' => $this->formatMessages($messages),
            'stream' => false,
            'keep_alive' => '30m',
        ]);

        if (!$response->successful()) {
            Log::error('Ollama API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'host' => $this->host,
            ]);
            throw new RuntimeException('Ollama API error: ' . $response->body());
        }

        return $response->json()['message']['content'] ?? '';
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        $model = $options['model'] ?? $this->model;

        $response = Http::withOptions([
            'stream' => true,
        ])->timeout($this->timeout)->post($this->host . '/api/chat', [
            'model' => $model,
            'messages' => $this->formatMessages($messages),
            'stream' => true,
            'keep_alive' => '30m',
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Ollama stream error: ' . $response->status());
        }

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(1024);
            $buffer .= $chunk;

            while (($newlinePos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $newlinePos);
                $buffer = substr($buffer, $newlinePos + 1);

                if (empty(trim($line))) {
                    continue;
                }

                $data = json_decode($line, true);
                if ($data && isset($data['message']['content'])) {
                    yield $data['message']['content'];
                }

                if ($data && isset($data['done']) && $data['done'] === true) {
                    return;
                }
            }
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        // Ollama nao suporta tools nativamente ainda
        // Fallback para chat simples
        $content = $this->chat($messages, $options);

        return [
            'content' => $content,
            'tool_calls' => [],
            'finish_reason' => 'stop',
            'usage' => [],
        ];
    }

    public function embedding(string $text): array
    {
        $response = Http::timeout($this->timeout)->post($this->host . '/api/embeddings', [
            'model' => $this->model,
            'prompt' => $text,
        ]);

        if (!$response->successful()) {
            throw new RuntimeException('Ollama Embedding error: ' . $response->body());
        }

        return $response->json()['embedding'] ?? [];
    }

    public function getDriverName(): string
    {
        return 'ollama';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    protected function formatMessages(array $messages): array
    {
        return array_map(function ($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }, $messages);
    }

    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->host . '/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function listModels(): array
    {
        try {
            $response = Http::timeout(10)->get($this->host . '/api/tags');
            if ($response->successful()) {
                return $response->json()['models'] ?? [];
            }
        } catch (\Exception $e) {
            Log::warning('Failed to list Ollama models: ' . $e->getMessage());
        }

        return [];
    }
}
