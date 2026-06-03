<?php

declare(strict_types=1);

namespace App\Core\IA\Http\Controllers;

use App\Core\IA\Embeddings\GeminiEmbeddingGenerator;
use App\Core\IA\Models\AIConversation;
use App\Core\IA\Models\AIMessage;
use App\Core\IA\Papiro\DecretacoesPapiro;
use App\Core\IA\Tools\LLPhant\CrossModuleTools;
use App\Core\IA\Tools\LLPhant\DecretosAnalyticsTools;
use App\Core\IA\Tools\LLPhant\DecretosTools;
use App\Core\IA\Tools\LLPhant\PaeAnalyticsTools;
use App\Core\IA\Tools\LLPhant\PaeTools;
use App\Core\IA\Tools\LLPhant\RatAnalyticsTools;
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
    private ?OpenAIChat $chat = null;
    private ?DoctrineVectorStore $vectorStore = null;
    private ?GeminiEmbeddingGenerator $embeddingGenerator = null;

    private function getChat(): OpenAIChat
    {
        return $this->chat ??= app(OpenAIChat::class);
    }

    private function getVectorStore(): DoctrineVectorStore
    {
        return $this->vectorStore ??= app(DoctrineVectorStore::class);
    }

    private function getEmbeddingGenerator(): GeminiEmbeddingGenerator
    {
        return $this->embeddingGenerator ??= app(GeminiEmbeddingGenerator::class);
    }

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

        try {
            $content = $this->getChat()->generateChat($messages);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Chat Error', [
                'error'  => $e->getMessage(),
                'driver' => 'gemini',
            ]);

            return response()->json([
                'conversation_id' => $conversationId,
                'content'         => $this->humanizeError($e),
                'driver'          => 'gemini',
                'error'           => true,
            ], 200);
        }

        $this->persistMessages($conversationId, $userInput, $content);

        // Adicionar contexto RAG na resposta
        $response['rag_context'] = $this->ragService->getContextData();

        return response()->json([
            'conversation_id'   => $conversationId,
            'content'           => $content,
            'driver'            => 'gemini',
            'execution_time_ms' => $elapsedMs,
        ]);
    }

    public function stream(Request $request): StreamedResponse
    {
        set_time_limit(0);

        $validated = $request->validate([
            'message'         => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid',
            'intent'          => 'nullable|string|max:100',
        ]);

        $conversationId = $this->resolveConversationId($validated['conversation_id'] ?? null);
        $userInput      = $validated['message'];

        $messages = $this->buildMessages($conversationId, $userInput);

        $this->registerTools();

        // generateChat (nao-stream) executa o ciclo completo de function calling.
        // generateChatStream nao fecha o ciclo das tools no LLPhant — ver doc/ARCHITECTURE.
        // Pegamos a resposta completa e fazemos pseudo-stream em chunks para preservar UX.
        try {
            $fullContent = $this->getChat()->generateChat($messages);
        } catch (\Throwable $err) {
            \Illuminate\Support\Facades\Log::error('AI Stream Error', [
                'error'           => $err->getMessage(),
                'conversation_id' => $conversationId,
                'driver'          => 'gemini',
            ]);

            $fullContent = $this->humanizeError($err);
            $isError     = true;
        }

        $isError = $isError ?? false;

        return response()->stream(function () use ($fullContent, $conversationId, $userInput, $isError) {
            echo 'data: ' . json_encode(['conversation_id' => $conversationId]) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();

            // Pseudo-streaming: fragmenta a resposta em chunks de ~32 chars para feedback progressivo.
            $chunks = mb_str_split($fullContent, 32);
            foreach ($chunks as $chunk) {
                echo 'data: ' . json_encode(['content' => $chunk]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
                usleep(20000);
            }

            echo "data: [DONE]\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();

            if (! $isError && $fullContent !== '') {
                $this->persistMessages($conversationId, $userInput, $fullContent);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'Connection'        => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function conversations(): JsonResponse
    {
        $userId   = Auth::id();
        $cacheKey = 'ai_conversations:user:' . $userId;

        $conversations = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($userId) {
            return AIConversation::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get(['id', 'title', 'created_at', 'updated_at'])
                ->toArray();
        });

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
        $ratTools          = new RatTools();
        $paeTools          = new PaeTools();
        $decretosTools     = new DecretosTools();
        $decretosAnalytics = new DecretosAnalyticsTools();
        $ratAnalytics      = new RatAnalyticsTools();
        $paeAnalytics      = new PaeAnalyticsTools();
        $crossModule       = new CrossModuleTools();

        $tools = [
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorMunicipio'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorProtocolo'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorId'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'resumoStatusRat'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorMunicipio'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorProtocolo'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'resumoStatusPae'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'listar_decretos_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_processo_decreto'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'obter_diagnostico_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_danos_socioeconomicos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'ranking_municipios_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'comparar_municipios_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'tendencia_anual_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'cobrade_mais_frequente'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'panorama_estado_decretos'),
            FunctionBuilder::buildFunctionInfo($ratAnalytics, 'ranking_municipios_rat'),
            FunctionBuilder::buildFunctionInfo($ratAnalytics, 'tendencia_anual_rat'),
            FunctionBuilder::buildFunctionInfo($paeAnalytics, 'ranking_municipios_pae'),
            FunctionBuilder::buildFunctionInfo($paeAnalytics, 'tendencia_anual_pae'),
            FunctionBuilder::buildFunctionInfo($crossModule, 'panorama_municipio'),
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

        $messages = [Message::system(DecretacoesPapiro::build())];

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
            $embedding = $this->getEmbeddingGenerator()->embedText($userInput);
            $documents = $this->getVectorStore()->similaritySearch($embedding, 3);

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
        $ratTools          = new RatTools();
        $paeTools          = new PaeTools();
        $decretosTools     = new DecretosTools();
        $decretosAnalytics = new DecretosAnalyticsTools();
        $ratAnalytics      = new RatAnalyticsTools();
        $paeAnalytics      = new PaeAnalyticsTools();
        $crossModule       = new CrossModuleTools();

        $this->getChat()->setTools([
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorMunicipio'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorProtocolo'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'buscarRatPorId'),
            FunctionBuilder::buildFunctionInfo($ratTools, 'resumoStatusRat'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorMunicipio'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'buscarPaePorProtocolo'),
            FunctionBuilder::buildFunctionInfo($paeTools, 'resumoStatusPae'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'listar_decretos_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_processo_decreto'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'obter_diagnostico_municipio'),
            FunctionBuilder::buildFunctionInfo($decretosTools, 'consultar_danos_socioeconomicos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'ranking_municipios_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'comparar_municipios_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'tendencia_anual_decretos'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'cobrade_mais_frequente'),
            FunctionBuilder::buildFunctionInfo($decretosAnalytics, 'panorama_estado_decretos'),
            FunctionBuilder::buildFunctionInfo($ratAnalytics, 'ranking_municipios_rat'),
            FunctionBuilder::buildFunctionInfo($ratAnalytics, 'tendencia_anual_rat'),
            FunctionBuilder::buildFunctionInfo($paeAnalytics, 'ranking_municipios_pae'),
            FunctionBuilder::buildFunctionInfo($paeAnalytics, 'tendencia_anual_pae'),
            FunctionBuilder::buildFunctionInfo($crossModule, 'panorama_municipio'),
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

    protected function humanizeError(\Throwable $e): string
    {
        $msg = $e->getMessage();

        if (str_contains($msg, '"code": 429') || str_contains($msg, 'RESOURCE_EXHAUSTED')) {
            return 'Limite de uso da IA atingido. Tente novamente em alguns minutos.';
        }

        if (str_contains($msg, 'API_KEY_INVALID') || str_contains($msg, 'API key expired') || str_contains($msg, '"code": 401') || str_contains($msg, '"code": 403')) {
            return 'Configuracao da IA invalida. Contate o suporte.';
        }

        if (str_contains($msg, 'cURL error 28') || str_contains($msg, 'Connection timed out') || str_contains($msg, 'Operation timed out')) {
            return 'Nao foi possivel alcancar o servico de IA. Verifique sua conexao e tente novamente.';
        }

        if (str_contains($msg, 'cURL error 6') || str_contains($msg, 'Could not resolve host') || str_contains($msg, 'cURL error 7')) {
            return 'Servico de IA temporariamente indisponivel.';
        }

        if (str_contains($msg, '"code": 400') || str_contains($msg, 'INVALID_ARGUMENT')) {
            return 'Nao foi possivel processar sua solicitacao. Reformule a pergunta e tente novamente.';
        }

        return 'Ocorreu um erro ao gerar a resposta. Tente novamente em instantes.';
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
        Cache::forget('ai_conversations:user:' . Auth::id());

        AIConversation::where('id', $conversationId)
            ->update(['title' => Str::limit($userMessage, 100)]);
    }
}
