import { ref, onMounted, onUnmounted } from 'vue';

// Import URL instead of constructor to manual bypass CORS
import AIWorkerUrl from '../Workers/ai.worker.js?url';

export function useHybridAI() {
    const isReady = ref(false);
    const isThinking = ref(false);
    const error = ref(null);
    const worker = ref(null);
    const currentResponse = ref('');

    // Initialize Worker
    const initWorker = () => {
        try {
            console.log("DEBUG: initWorker called via Blob Proxy");
            if (typeof Worker !== 'undefined') {
                console.log("DEBUG: Creating Worker Blob for URL:", AIWorkerUrl);

                // Create a Blob that imports the external Vite worker script
                // This bypasses the "SecurityError: Failed to construct 'Worker': Script at ... cannot be accessed"
                const blobContent = `import "${AIWorkerUrl}";`;
                const blob = new Blob([blobContent], { type: 'application/javascript' });
                const workerUrl = URL.createObjectURL(blob);

                worker.value = new Worker(workerUrl, { type: 'module' });

                console.log("DEBUG: Worker created successfully");

                worker.value.onmessage = (e) => {
                    const { type, message } = e.data;
                    console.log("DEBUG: Worker msg", type, message);
                    if (type === 'READY') isReady.value = true;
                    if (type === 'ERROR') {
                        console.error('Worker Error:', message);
                        error.value = message;
                    }
                };
            }
        } catch (e) {
            console.error("DEBUG: Failed to init worker", e);
            error.value = "Failed to start AI: " + e.message;
        }
    };

    const terminateWorker = () => {
        if (worker.value) worker.value.terminate();
    };

    onMounted(() => {
        initWorker();
    });

    onUnmounted(() => {
        terminateWorker();
    });

    // Main interaction function
    const ask = async (prompt, onChunk = () => { }, onDone = () => { }) => {
        isThinking.value = true;
        currentResponse.value = '';
        error.value = null;

        return new Promise((resolve, reject) => {
            if (!isReady.value) {
                // Fallback to server immediately if worker not ready
                console.log('Worker not ready, falling back to server...');
                handleServerStream(prompt, onChunk, onDone).then(resolve).catch(reject);
                return;
            }

            // Create a one-time handler for the classification result
            const handleClassification = async (e) => {
                const { type, payload } = e.data;

                if (type === 'CLASSIFICATION_RESULT') {
                    worker.value.removeEventListener('message', handleClassification);

                    console.log(`Intent detected: ${payload.intent} (Local: ${payload.isLocal})`);

                    if (payload.isLocal) {
                        const localReply = getLocalResponse(payload.intent);
                        simulateStream(localReply, onChunk, onDone);
                        resolve(localReply);
                    } else {
                        await handleServerStream(prompt, onChunk, onDone);
                        resolve();
                    }
                }
            };

            worker.value.addEventListener('message', handleClassification);
            worker.value.postMessage({ type: 'CLASSIFY', payload: { text: prompt } });
        });
    };

    const handleServerStream = async (prompt, onChunk, onDone) => {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const isDev = import.meta.env.DEV || window.location.hostname === 'localhost';
            const endpoint = isDev ? '/api/ai/dev/chat/stream' : '/api/ai/chat/stream';

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/event-stream',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ message: prompt })
            });

            if (!response.ok) {
                throw new Error(`Server error: ${response.status}`);
            }

            const reader = response.body.getReader();
            const decoder = new TextDecoder();

            while (true) {
                const { done, value } = await reader.read();
                if (done) break;

                const chunk = decoder.decode(value);
                const lines = chunk.split('\n');

                for (const line of lines) {
                    if (line.startsWith('data: ')) {
                        const data = line.slice(6);
                        if (data === '[DONE]') continue;

                        try {
                            const json = JSON.parse(data);
                            if (json.content) {
                                currentResponse.value += json.content;
                                onChunk(json.content);
                            }
                        } catch (e) {
                            // In case of split JSON, simplified handling
                        }
                    }
                }
            }
            onDone();
        } catch (err) {
            console.error('Stream Error:', err);
            error.value = "Erro ao conectar com o servidor da Defesa Civil.";
            currentResponse.value += "\n[Erro de conexão]";
            isThinking.value = false;
        } finally {
            isThinking.value = false;
        }
    };

    const getLocalResponse = (intent) => {
        const responses = {
            'local_greeting': "Olá! Sou o Assistente Inteligente da Defesa Civil (NewSDC). Estou operando localmente para maior velocidade. Como posso ajudar com os protocolos hoje?",
            'local_help': "Posso ajudar com: \n1. Consulta de Protocolos RAT\n2. Alertas Meteorológicos\n3. Gestão de Abrigos\n\nO que você precisa?",
            'local_clear': "Histórico limpo."
        };
        return responses[intent] || "Processando localmente...";
    };

    const simulateStream = (text, onChunk, onDone) => {
        let i = 0;
        const interval = setInterval(() => {
            if (i < text.length) {
                const char = text.charAt(i);
                currentResponse.value += char;
                onChunk(char);
                i++;
            } else {
                clearInterval(interval);
                isThinking.value = false;
                onDone();
            }
        }, 15);
    };

    return {
        isReady,
        isThinking,
        error,
        ask,
        currentResponse
    };
}
