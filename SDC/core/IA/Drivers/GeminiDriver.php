<?php

namespace App\Core\IA\Drivers;

use App\Core\IA\Contracts\LLMDriverInterface;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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
        $this->apiKey   = $config['api_key']  ?? '';
        $this->model    = $config['model']     ?? 'gemini-pro';
        $this->baseUrl  = $config['base_url']  ?? 'https://generativelanguage.googleapis.com/v1beta';
        $this->maxTokens = $config['max_tokens'] ?? 4096;

        if (empty($this->apiKey)) {
            throw new RuntimeException('Google AI API key not configured');
        }
    }

    public function chat(array $messages, array $options = []): string
    {
        [$systemInstruction, $contents] = $this->extractSystemAndContents($messages);

        $payload = $this->buildPayload($contents, $systemInstruction, $options);

        $response = Http::timeout(60)->post(
            $this->buildUrl('generateContent', $options),
            $payload
        );

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? '';
    }

    public function chatStream(array $messages, array $options = []): Generator
    {
        [$systemInstruction, $contents] = $this->extractSystemAndContents($messages);

        $payload = $this->buildPayload($contents, $systemInstruction, $options);

        $response = Http::withOptions(['stream' => true])->timeout(120)->post(
            $this->buildUrl('streamGenerateContent', $options),
            $payload
        );

        $body   = $response->getBody();
        $buffer = '';

        while (!$body->eof()) {
            $chunk = $body->read(512);

            if ($chunk === '') {
                usleep(1000);
                continue;
            }

            $buffer .= $chunk;

            while (($data = $this->tryExtractJsonObject($buffer)) !== false) {
                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    yield $data['candidates'][0]['content']['parts'][0]['text'];
                }
            }
        }

        while (($data = $this->tryExtractJsonObject($buffer)) !== false) {
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                yield $data['candidates'][0]['content']['parts'][0]['text'];
            }
        }
    }

    public function chatWithTools(array $tools, array $messages, array $options = []): array
    {
        [$systemInstruction, $contents] = $this->extractSystemAndContents($messages);

        $functionDeclarations = array_map(fn($tool) => [
            'name'        => $tool->getName(),
            'description' => $tool->getDescription(),
            'parameters'  => $tool->getParametersSchema(),
        ], $tools);

        $payload = $this->buildPayload($contents, $systemInstruction, $options, [
            'tools' => [['functionDeclarations' => $functionDeclarations]],
        ]);

        $response = Http::timeout(60)->post(
            $this->buildUrl('generateContent', $options),
            $payload
        );

        if (!$response->successful()) {
            throw new RuntimeException('Gemini API error: ' . $response->body());
        }

        $data      = $response->json();
        $parts     = $data['candidates'][0]['content']['parts'] ?? [];
        $content   = null;
        $toolCalls = [];

        foreach ($parts as $part) {
            if (isset($part['text'])) {
                $content = $part['text'];
            } elseif (isset($part['functionCall'])) {
                $toolCalls[] = [
                    'id'       => (string) Str::uuid(),
                    'type'     => 'function',
                    'function' => [
                        'name'      => $part['functionCall']['name'],
                        'arguments' => json_encode($part['functionCall']['args'] ?? []),
                    ],
                ];
            }
        }

        return [
            'content'       => $content,
            'tool_calls'    => $toolCalls,
            'finish_reason' => $data['candidates'][0]['finishReason'] ?? null,
            'usage'         => $data['usageMetadata'] ?? [],
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

    protected function extractSystemAndContents(array $messages): array
    {
        $systemPrompt = null;
        $contents     = [];

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemPrompt = $msg['content'];
                continue;
            }

            $contents[] = [
                'role'  => $msg['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $msg['content']]],
            ];
        }

        return [$systemPrompt, $contents];
    }

    protected function buildUrl(string $method, array $options = []): string
    {
        $model = $options['model'] ?? $this->model;

        return $this->baseUrl . '/models/' . $model . ':' . $method . '?key=' . $this->apiKey;
    }

    private function buildPayload(
        array $contents,
        ?string $systemInstruction,
        array $options,
        array $extra = []
    ): array {
        $payload = array_merge([
            'contents'         => $contents,
            'generationConfig' => ['maxOutputTokens' => $options['max_tokens'] ?? $this->maxTokens],
        ], $extra);

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        return $payload;
    }

    private function tryExtractJsonObject(string &$buffer): array|false
    {
        $buffer = ltrim($buffer, " \n\r\t[,]");

        if ($buffer === '' || $buffer[0] !== '{') {
            return false;
        }

        $length   = strlen($buffer);
        $depth    = 0;
        $inString = false;
        $escape   = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $buffer[$i];

            if ($escape) {
                $escape = false;
                continue;
            }

            if ($char === '\\' && $inString) {
                $escape = true;
                continue;
            }

            if ($char === '"') {
                $inString = !$inString;
                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;

                if ($depth === 0) {
                    $jsonStr = substr($buffer, 0, $i + 1);
                    $buffer  = substr($buffer, $i + 1);

                    $data = json_decode($jsonStr, true);

                    if ($data === null) {
                        return false;
                    }

                    return $data;
                }
            }
        }

        return false;
    }
}
