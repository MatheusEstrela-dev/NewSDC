<?php

declare(strict_types=1);

namespace App\Core\IA\Http\Controllers;

use App\Core\IA\Embeddings\GeminiEmbeddingGenerator;
use App\Core\IA\Models\AIConversation;
use App\Core\IA\Models\AIMessage;
use App\Core\IA\Papiro\SdcPapiro;
use App\Core\IA\Tools\LLPhant\DecretosTools;
use App\Core\IA\Tools\LLPhant\PaeTools;
use App\Core\IA\Tools\LLPhant\RatTools;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use LLPhant\Chat\FunctionInfo\FunctionBuilder;
use LLPhant\Chat\Message;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Embeddings\VectorStores\Doctrine\DoctrineVectorStore;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        protected OpenAIChat $chat,
        protected DoctrineVectorStore $vectorStore,
        protected GeminiEmbeddingGenerator $embeddingGenerator,
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid',
            'intent'          => 'nullable|string|max:100',
        ]);

        $startedAt      = hrtime(true);
        $conversationId = $this->resolveConversationId($validated['conversation_id'] ?? null);
        $userInput      = $validated['message'];

        $messages = $this->buildMessages($conversationId, $userInput);

        $this->registerTools();

        $content = $this->chat->generateChat($messages);

        $this->persistMessages($conversationId, $userInput, $content);

        $elapsedMs = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        return response()->json([
            'conversation_id'   => $conversationId,
            'content'           => $content,
            'driver'            => 'gemini',
            'execution_time_ms' => $elapsedMs,
        ]);
    }

    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message'         => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid',
            'intent'          => 'nullable|string|max:100',
        ]);

        $conversationId = $this->resolveConversationId($validated['conversation_id'] ?? null);
        $userInput      = $validated['message'];

        $messages = $this->buildMessages($conversationId, $userInput);

        $this->registerTools();

        $stream = $this->chat->generateChatStream($messages);

        return response()->stream(function () use ($stream, $conversationId, $userInput) {
            echo 'data: ' . json_encode(['conversation_id' => $conversationId]) . "\n\n";
            ob_flush();
            flush();

            $fullContent = '';

            while (! $stream->eof()) {
                $chunk = $stream->read(512);

                if ($chunk === '' || $chunk === false) {
                    continue;
                }

                $fullContent .= $chunk;

                echo 'data: ' . json_encode(['content' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();

            $this->persistMessages($conversationId, $userInput, $fullContent);
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function conversations(): JsonResponse
    {
        $conversations = AIConversation::where('user_id', Auth::id())
            ->orderBy('updated_at', 'desc')
            ->limit(50)
            ->get(['id', 'title', 'created_at', 'updated_at']);

        return response()->json($conversations);
    }

    public function messages(string $id): JsonResponse
    {
        $conversation = AIConversation::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'conversation' => $conversation,
            'messages'     => $messages,
        ]);
    }

    public function deleteConversation(string $id): JsonResponse
    {
        $conversation = AIConversation::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    public function tools(): JsonResponse
    {
        $ratTools      = new RatTools();
        $paeTools      = new PaeTools();
        $decretosTools = new DecretosTools();

        $tools = [
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorMunicipio'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorProtocolo'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorId'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorMunicipio'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorProtocolo'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'resumoStatusPae'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'listar_decretos_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_processo_decreto'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'obter_diagnostico_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_danos_socioeconomicos'),
        ];

        $payload = array_map(fn ($tool) => [
            'name'        => $tool->name,
            'description' => $tool->description,
        ], $tools);

        return response()->json($payload);
    }

    protected function buildMessages(string $conversationId, string $userInput): array
    {
        $cacheKey = 'ai_history:' . $conversationId;

        $history = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($conversationId) {
            return AIMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(['role', 'content'])
                ->reverse()
                ->values()
                ->toArray();
        });

        $messages = [Message::system(SdcPapiro::build())];

        foreach ($history as $msg) {
            $messages[] = $msg['role'] === 'user'
                ? Message::user($msg['content'])
                : Message::assistant($msg['content']);
        }

        $ragContext = $this->buildRagContext($userInput);
        $enrichedInput = $ragContext ? $userInput.$ragContext : $userInput;

        $messages[] = Message::user($enrichedInput);

        return $messages;
    }

    protected function buildRagContext(string $userInput): string
    {
        try {
            $embedding = $this->embeddingGenerator->embedText($userInput);
            $documents = $this->vectorStore->similaritySearch($embedding, 3);

            if (empty($documents)) {
                return '';
            }

            $contextParts = [];
            foreach ($documents as $doc) {
                $contextParts[] = '['.($doc->sourceName ?? 'RAT').'] '.$doc->content;
            }

            return "\n\n--- CONTEXTO DO BANCO (RAG) ---\n".implode("\n", $contextParts)."\n---";
        } catch (\Exception) {
            return '';
        }
    }

    protected function registerTools(): void
    {
        $ratTools      = new RatTools();
        $paeTools      = new PaeTools();
        $decretosTools = new DecretosTools();

        $this->chat->setTools([
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorMunicipio'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorProtocolo'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorId'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorMunicipio'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorProtocolo'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'resumoStatusPae'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'listar_decretos_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_processo_decreto'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'obter_diagnostico_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_danos_socioeconomicos'),
        ]);
    }

    protected function resolveConversationId(?string $conversationId): string
    {
        if ($conversationId) {
            $exists = AIConversation::where('id', $conversationId)
                ->where('user_id', Auth::id())
                ->exists();

            if ($exists) {
                return $conversationId;
            }
        }

        $conversation = AIConversation::create([
            'user_id' => Auth::id(),
            'title'   => 'Nova conversa',
        ]);

        return $conversation->id;
    }

    protected function persistMessages(string $conversationId, string $userMessage, string $assistantMessage): void
    {
        AIMessage::create([
            'id'              => (string) Str::uuid(),
            'conversation_id' => $conversationId,
            'role'            => 'user',
            'content'         => $userMessage,
        ]);

        AIMessage::create([
            'id'              => (string) Str::uuid(),
            'conversation_id' => $conversationId,
            'role'            => 'assistant',
            'content'         => $assistantMessage,
        ]);

        Cache::forget('ai_history:' . $conversationId);

        AIConversation::where('id', $conversationId)
            ->update(['title' => Str::limit($userMessage, 100)]);
    }
}
