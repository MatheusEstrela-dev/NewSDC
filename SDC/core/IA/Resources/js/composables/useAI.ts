import { ref, computed } from 'vue';

interface Message {
    id: string;
    role: 'user' | 'assistant' | 'system';
    content: string;
    timestamp: Date;
    toolResults?: any[];
}

interface ProcessedMessage {
    text: string;
    intent: string;
    entities: Record<string, any>;
}

interface AIResponse {
    conversation_id: string;
    content: string;
    tool_results: any[];
    driver: string;
    model: string;
    execution_time_ms: number;
}

export function useAI() {
    const isLoading = ref(false);
    const isStreaming = ref(false);
    const messages = ref<Message[]>([]);
    const conversationId = ref<string | null>(null);
    const error = ref<string | null>(null);

    let worker: Worker | null = null;

    const initWorker = () => {
        if (worker) return;

        try {
            worker = new Worker(
                new URL('../workers/ai.worker.js', import.meta.url),
                { type: 'module' }
            );
        } catch (e) {
            console.warn('Web Worker not available, local processing disabled');
        }
    };

    const preprocessLocal = async (text: string): Promise<ProcessedMessage> => {
        if (!worker) {
            return { text, intent: 'geral', entities: {} };
        }

        return new Promise((resolve) => {
            const timeout = setTimeout(() => {
                resolve({ text, intent: 'geral', entities: {} });
            }, 3000);

            worker!.onmessage = (e) => {
                clearTimeout(timeout);
                resolve(e.data);
            };

            worker!.postMessage({ type: 'preprocess', text });
        });
    };

    const send = async (message: string, useStreaming = false): Promise<AIResponse | null> => {
        if (!message.trim()) return null;

        isLoading.value = true;
        error.value = null;

        const userMessage: Message = {
            id: crypto.randomUUID(),
            role: 'user',
            content: message,
            timestamp: new Date(),
        };

        messages.value.push(userMessage);

        try {
            initWorker();
            const processed = await preprocessLocal(message);

            if (useStreaming) {
                return await sendWithStreaming(message, processed.intent);
            }

            const response = await fetch('/api/ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    message: message,
                    intent: processed.intent,
                    conversation_id: conversationId.value,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data: AIResponse = await response.json();

            conversationId.value = data.conversation_id;

            const assistantMessage: Message = {
                id: crypto.randomUUID(),
                role: 'assistant',
                content: data.content,
                timestamp: new Date(),
                toolResults: data.tool_results,
            };

            messages.value.push(assistantMessage);

            return data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Erro desconhecido';
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const sendWithStreaming = async (message: string, intent: string): Promise<AIResponse | null> => {
        isStreaming.value = true;

        const assistantMessage: Message = {
            id: crypto.randomUUID(),
            role: 'assistant',
            content: '',
            timestamp: new Date(),
        };

        messages.value.push(assistantMessage);
        const msgIndex = messages.value.length - 1;

        try {
            const response = await fetch('/api/ai/chat/stream', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    message,
                    intent,
                    conversation_id: conversationId.value,
                }),
            });

            const reader = response.body?.getReader();
            const decoder = new TextDecoder();

            if (!reader) throw new Error('Stream not available');

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);

                        if (data === '[DONE]') {
                            break;
                        }

                        try {
                            const parsed = JSON.parse(data);

                            if (parsed.conversation_id) {
                                conversationId.value = parsed.conversation_id;
                            }

                            if (parsed.content) {
                                messages.value[msgIndex].content += parsed.content;
                            }
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                }
            }

            return {
                conversation_id: conversationId.value || '',
                content: messages.value[msgIndex].content,
                tool_results: [],
                driver: '',
                model: '',
                execution_time_ms: 0,
            };
        } finally {
            isStreaming.value = false;
            isLoading.value = false;
        }
    };

    const loadConversation = async (convId: string) => {
        try {
            const response = await fetch(`/api/ai/conversations/${convId}/messages`, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (!response.ok) throw new Error('Failed to load conversation');

            const data = await response.json();
            conversationId.value = convId;

            messages.value = data.messages.map((msg: any) => ({
                id: msg.id,
                role: msg.role,
                content: msg.content,
                timestamp: new Date(msg.created_at),
            }));
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Erro ao carregar conversa';
        }
    };

    const clearMessages = () => {
        messages.value = [];
        conversationId.value = null;
        error.value = null;
    };

    const deleteConversation = async (convId: string) => {
        try {
            await fetch(`/api/ai/conversations/${convId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
                },
            });

            if (conversationId.value === convId) {
                clearMessages();
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Erro ao deletar conversa';
        }
    };

    return {
        send,
        messages,
        isLoading,
        isStreaming,
        conversationId,
        error,
        loadConversation,
        clearMessages,
        deleteConversation,
    };
}
