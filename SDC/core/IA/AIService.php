<?php

declare(strict_types=1);

namespace App\Core\IA;

use App\Core\IA\Contracts\LLMDriverInterface;
use App\Core\IA\Contracts\ToolInterface;
use App\Core\IA\Drivers\OpenAIDriver;
use App\Core\IA\Drivers\ClaudeDriver;
use App\Core\IA\Drivers\GeminiDriver;
use App\Core\IA\Drivers\OllamaDriver;
use App\Core\IA\DTOs\ChatInputDTO;
use App\Core\IA\Models\AIConversation;
use App\Core\IA\Models\AIMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Generator;

class AIService
{
    protected LLMDriverInterface $driver;
    protected array $tools = [];
    protected ?string $conversationId = null;
    protected array $messages = [];

    public function __construct(?string $driverName = null)
    {
        $this->driver = $this->resolveDriver($driverName ?? config('ai.default_driver', 'openai'));
    }

    protected function resolveDriver(string $name): LLMDriverInterface
    {
        return match ($name) {
            'openai' => new OpenAIDriver(),
            'claude' => new ClaudeDriver(),
            'gemini' => new GeminiDriver(),
            'ollama' => new OllamaDriver(),
            'mock' => new \App\Core\IA\Drivers\MockDriver(),
            default => throw new InvalidArgumentException("Driver [$name] not supported"),
        };
    }

    public function registerTool(ToolInterface $tool): self
    {
        $this->tools[$tool->getName()] = $tool;
        return $this;
    }

    public function registerTools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->registerTool($tool);
        }
        return $this;
    }

    public function conversation(?string $conversationId): self
    {
        $this->conversationId = $conversationId;
        if ($conversationId) {
            $this->loadConversationHistory();
        }
        return $this;
    }

    protected function loadConversationHistory(): void
    {
        if (!$this->conversationId) return;

        // Verify ownership/existence implicitly via scope or simple check
        $conversation = AIConversation::find($this->conversationId);
        if (!$conversation) {
            // Log::warning("Conversation {$this->conversationId} not found.");
            return;
        }

        $maxHistory = (int) config('ai.conversation.max_history', 20);

        $messages = AIMessage::where('conversation_id', $this->conversationId)
            ->orderBy('created_at', 'desc')
            ->limit($maxHistory)
            ->get()
            ->reverse();

        $this->messages = $messages->map(fn($msg) => [
            'role' => $msg->role,
            'content' => $msg->content,
        ])->values()->toArray();
    }

    /**
     * @throws RuntimeException
     */
    public function chat(ChatInputDTO $input): array
    {
        $options = $input->options;
        $systemPrompt = $options['system_prompt'] ?? config('ai.system_prompt');

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages = array_merge($messages, $this->messages);
        $messages[] = ['role' => 'user', 'content' => $input->message];

        $startTime = microtime(true);
        $toolResults = [];

        try {
            if (!empty($this->tools) && config('ai.tools.enabled', true)) {
                $response = $this->executeWithTools($messages, $options);
                $toolResults = $response['tool_results'] ?? [];
            } else {
                $content = $this->driver->chat($messages, $options);
                $response = ['content' => $content, 'tool_calls' => []];
            }
        } catch (\Exception $e) {
            Log::error('AI Service Error', [
                'error' => $e->getMessage(), 
                'trace' => $e->getTraceAsString(),
                'driver' => $this->driver->getDriverName()
            ]);
            
            // Re-throw as a safe RuntimeException or handle gracefully depending on requirement
            // Here we re-throw to let the controller handle the 500 response
            throw new RuntimeException("Falha ao processar solicitação de IA: " . $e->getMessage(), 0, $e);
        }

        $executionTime = (microtime(true) - $startTime) * 1000;

        $this->messages[] = ['role' => 'user', 'content' => $input->message];
        $this->messages[] = ['role' => 'assistant', 'content' => $response['content']];

        if ($this->conversationId && config('ai.conversation.store_messages', true)) {
            $this->saveMessages($input->message, $response['content'], $toolResults);
        }

        return [
            'conversation_id' => $this->conversationId,
            'content' => $response['content'],
            'tool_results' => $toolResults,
            'driver' => $this->driver->getDriverName(),
            'model' => $this->driver->getModel(),
            'execution_time_ms' => round($executionTime, 2),
        ];
    }

    public function chatStream(ChatInputDTO $input): Generator
    {
        $options = $input->options;
        $systemPrompt = $options['system_prompt'] ?? config('ai.system_prompt');

        $messages = [];
        if ($systemPrompt) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages = array_merge($messages, $this->messages);
        $messages[] = ['role' => 'user', 'content' => $input->message];

        $fullContent = '';

        // Wrap generator in try-catch logic handled by the driver ideally, 
        // but here we just pass through.
        try {
            foreach ($this->driver->chatStream($messages, $options) as $chunk) {
                $fullContent .= $chunk;
                yield $chunk;
            }
        } catch (\Exception $e) {
            Log::error('AI Stream Error', ['error' => $e->getMessage()]);
            yield " [Erro ao gerar resposta: " . $e->getMessage() . "]";
        }

        $this->messages[] = ['role' => 'user', 'content' => $input->message];
        $this->messages[] = ['role' => 'assistant', 'content' => $fullContent];

        if ($this->conversationId && config('ai.conversation.store_messages', true)) {
            $this->saveMessages($input->message, $fullContent, []);
        }
    }

    protected function executeWithTools(array $messages, array $options): array
    {
        $tools = array_values($this->tools);
        $maxIterations = 5;
        $iteration = 0;
        $toolResults = [];

        while ($iteration < $maxIterations) {
            $response = $this->driver->chatWithTools($tools, $messages, $options);

            if (empty($response['tool_calls'])) {
                return ['content' => $response['content'], 'tool_results' => $toolResults];
            }

            foreach ($response['tool_calls'] as $toolCall) {
                $toolName = $toolCall['function']['name'];
                $arguments = json_decode($toolCall['function']['arguments'], true) ?? [];

                if (!isset($this->tools[$toolName])) {
                    Log::warning("Tool not found: $toolName");
                    continue;
                }

                try {
                    $result = $this->tools[$toolName]->execute($arguments);
                    $toolResults[] = ['tool' => $toolName, 'arguments' => $arguments, 'result' => $result];

                    $messages[] = ['role' => 'assistant', 'content' => null, 'tool_calls' => [$toolCall]];
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => is_string($result) ? $result : json_encode($result),
                    ];
                } catch (\Exception $e) {
                    Log::error("Tool execution error: $toolName", ['error' => $e->getMessage()]);
                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'],
                        'content' => json_encode(['error' => $e->getMessage()]),
                    ];
                }
            }

            $iteration++;
        }

        return ['content' => $this->driver->chat($messages, $options), 'tool_results' => $toolResults];
    }

    protected function saveMessages(string $userMessage, string $assistantMessage, array $toolResults): void
    {
        if (!$this->conversationId) {
            $uuid = (string) Str::uuid();
            AIConversation::create([
                'id' => $uuid,
                'user_id' => Auth::id(),
                'title' => Str::limit($userMessage, 100),
            ]);
            $this->conversationId = $uuid;
        }

        AIMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $this->conversationId,
            'role' => 'user',
            'content' => $userMessage,
        ]);

        AIMessage::create([
            'id' => (string) Str::uuid(),
            'conversation_id' => $this->conversationId,
            'role' => 'assistant',
            'content' => $assistantMessage,
            'tool_calls' => !empty($toolResults) ? $toolResults : null,
        ]);
    }

    public function getOrCreateConversation(?string $conversationId = null): string
    {
        if ($conversationId && AIConversation::where('id', $conversationId)->exists()) {
            $this->conversationId = $conversationId;
            $this->loadConversationHistory();
            return $conversationId;
        }

        $uuid = (string) Str::uuid();
        $conversation = AIConversation::create([
            'id' => $uuid,
            'user_id' => Auth::id(),
        ]);

        $this->conversationId = $uuid;
        return $uuid;
    }

    public function getDriver(): LLMDriverInterface
    {
        return $this->driver;
    }

    public function getTools(): array
    {
        return $this->tools;
    }

    public function clearHistory(): self
    {
        $this->messages = [];
        return $this;
    }
}
