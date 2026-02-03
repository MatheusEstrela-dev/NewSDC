<?php

declare(strict_types=1);

namespace App\Core\IA\Http\Controllers;

use App\Core\IA\AIService;
use App\Core\IA\DTOs\ChatInputDTO;
use App\Core\IA\Models\AIConversation;
use App\Core\IA\Services\RagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    protected AIService $aiService;
    protected RagService $ragService;

    public function __construct()
    {
        $this->aiService = new AIService();
        $this->ragService = new RagService();
    }

    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid',
            'intent' => 'nullable|string|max:100',
            'options' => 'nullable|array',
        ]);

        $conversationId = $validated['conversation_id'] ?? null;

        if ($conversationId) {
            $this->aiService->conversation($conversationId);
        } else {
            $conversationId = $this->aiService->getOrCreateConversation();
            $this->aiService->conversation($conversationId);
        }

        // RAG: Enriquecer mensagem com dados do banco
        $enrichedMessage = $this->ragService->enrichMessage($validated['message']);

        $dto = new ChatInputDTO(
            message: $enrichedMessage,
            conversationId: $conversationId,
            intent: $validated['intent'] ?? null,
            options: $validated['options'] ?? []
        );

        $response = $this->aiService->chat($dto);

        // Adicionar contexto RAG na resposta
        $response['rag_context'] = $this->ragService->getContextData();

        return response()->json($response);
    }

    public function stream(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid',
            'intent' => 'nullable|string|max:100',
            'options' => 'nullable|array',
        ]);

        $conversationId = $validated['conversation_id'] ?? null;

        if ($conversationId) {
            $this->aiService->conversation($conversationId);
        } else {
            $conversationId = $this->aiService->getOrCreateConversation();
            $this->aiService->conversation($conversationId);
        }

        // RAG: Enriquecer mensagem com dados do banco
        $enrichedMessage = $this->ragService->enrichMessage($validated['message']);

        $dto = new ChatInputDTO(
            message: $enrichedMessage,
            conversationId: $conversationId,
            intent: $validated['intent'] ?? null,
            options: $validated['options'] ?? []
        );

        return response()->stream(function () use ($dto, $conversationId) {
            echo "data: " . json_encode(['conversation_id' => $conversationId]) . "\n\n";
            ob_flush();
            flush();

            foreach ($this->aiService->chatStream($dto) as $chunk) {
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            }

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
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

    public function messages(string $conversationId): JsonResponse
    {
        $conversation = AIConversation::where('id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $messages = $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get(['id', 'role', 'content', 'created_at']);

        return response()->json([
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }

    public function deleteConversation(string $conversationId): JsonResponse
    {
        $conversation = AIConversation::where('id', $conversationId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json(['success' => true]);
    }

    public function tools(): JsonResponse
    {
        $tools = [];

        foreach ($this->aiService->getTools() as $name => $tool) {
            $tools[] = [
                'name' => $name,
                'description' => $tool->getDescription(),
                'parameters' => $tool->getParametersSchema(),
            ];
        }

        return response()->json($tools);
    }
}
