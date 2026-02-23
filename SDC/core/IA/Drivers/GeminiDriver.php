<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiDriver implements LLMDriverInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $maxTokens;

    public function __construct()
    {
        $config = config('ai.drivers.gemini');
        $this->apiKey = $config['api_key'] ?? '';
        $this->model = $config['model'] ?? 'gemini-pro';
        $this->baseUrl = $config['base_url'] ?? 'https://generativelanguage.googleapis.com/v1beta';
        $this->maxTokens = $config['max_tokens'] ?? 4096;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Google AI API key not configured');
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        $contents = $this->convertMessages($messages);

        $response = Http::timeout(60)->post(
            $this->baseUrl . '/models/' . ($options['model'] ?? $this->model) . ':generateContent?key=' . $this->apiKey,
            [
                'contents' => $contents,
                'generationConfig' => ['maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens],
            ]
        );

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        $contents = $this->convertMessages($messages);

        $response = Http::withOptions(['stream' => true])->timeout(120)->post(
            $this->baseUrl . '/models/' . ($options['model'] ?? $this->model) . ':streamGenerateContent?key=' . $this->apiKey,
            ['contents' => $contents, 'generationConfig' => ['maxOutputTokens' => $this->maxTokens]]
        );

        $body = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $buffer .= $body->read(1024);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = substr($buffer, 0, $pos);
                $buffer = substr($buffer, $pos + 1);
                if (empty(trim($line))) continue;
                $data = json_decode($line, true);
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    yield $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        $contents = $this->convertMessages($messages);

        $functionDeclarations = array_map(fn($tool) => [
            'name' => $tool->getName(),
            'description' => $tool->getDescription(),
            'parameters' => $tool->getParametersSchema(),
        ], $tools);

        $response = Http::timeout(60)->post(
            $this->baseUrl . '/models/' . ($options['model'] ?? $this->model) . ':generateContent?key=' . $this->apiKey,
            [
                'contents' => $contents,
                'tools' => [['functionDeclarations' => $functionDeclarations]],
                'generationConfig' => ['maxOutputTokens' => $this->maxTokens],
            ]
        );

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        $data = $response->json();
        $parts = $data['candidates'][0]['content']['parts'] ?? [];
        $content = null;
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) $content = $part['text'];
            elseif (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id' => uniqid('call_'),
                    'type' => 'function',
                    'function' => [
                        'name' => $part['functionCall']['name'],
                        'arguments' => json_encode($part['functionCall']['args'] ?? []),
                    ],
                ];
            }
        }

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
            'usage' => $data['usageMetadata'] ?? [],
        ];
    }

    public function embedding(string $text): array
    {
        $response = Http::timeout(30)->post(
            $this->baseUrl . '/models/embedding-001:embedContent?key=' . $this->apiKey,
            ['model' => 'models/embedding-001', 'content' => ['parts' => [['text' => $text]]]]
        );

        if (!$response->successful()) {
            throw new RuntimeException('Gemini Embedding error: ' . $response->body());
        }

        return $response->json()['embedding']['values'] ?? [];
    }

    public function getDriverName(): string
    {
        return 'gemini';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    protected function convertMessages(array $messages): array
    {
        $contents = [];
        $system = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
                continue;
            }
            $contents[] = [
                'role' => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        if ($system && !empty($contents)) {
            $contents[0]['parts'][0]['text'] = $system . "\n\n" . $contents[0]['parts'][0]['text'];
        }

        return $contents;
    }
}
