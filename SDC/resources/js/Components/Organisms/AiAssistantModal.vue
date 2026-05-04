<script setup>
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useHybridAI } from '@/Composables/useHybridAI';
import {
    ChatBubbleLeftIcon,
    PaperAirplaneIcon,
    MagnifyingGlassIcon,
    UserIcon,
    CpuChipIcon,
    ChevronLeftIcon,
    Squares2X2Icon,
    CloudIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    Cog6ToothIcon,
    QuestionMarkCircleIcon,
    PaperClipIcon,
    ArrowRightIcon,
    ShieldCheckIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const { ask, isThinking, isReady, error } = useHybridAI();

const isSidebarOpen = ref(true);
const userInput = ref('');
const messagesContainer = ref(null);
const messages = ref([]);

const conversations = ref([]);
const conversationId = ref(null);
const searchQuery = ref('');
const isLoadingConversations = ref(false);

const isDev = import.meta.env.DEV || window.location.hostname === 'localhost';
const API_BASE = isDev ? '/api/ai/dev' : '/api/ai';

const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) return conversations.value;
    const q = searchQuery.value.toLowerCase();
    return conversations.value.filter(c => c.title?.toLowerCase().includes(q));
});

const getHeaders = () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    return {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'X-Requested-With': 'XMLHttpRequest',
    };
};

const loadConversations = async () => {
    try {
        isLoadingConversations.value = true;
        const res = await fetch(`${API_BASE}/conversations`, { headers: getHeaders() });
        if (res.ok) {
            conversations.value = await res.json();
        }
    } catch (e) {
        // silent fail — sidebar is non-critical
    } finally {
        isLoadingConversations.value = false;
    }
};

const selectConversation = async (conv) => {
    if (conversationId.value === conv.id) return;

    conversationId.value = conv.id;
    messages.value = [];

    try {
        const res = await fetch(`${API_BASE}/conversations/${conv.id}/messages`, { headers: getHeaders() });
        if (res.ok) {
            const data = await res.json();
            messages.value = data.messages.map(m => ({
                id: m.id,
                role: m.role,
                content: m.content,
                timestamp: new Date(m.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
            }));
            await scrollToBottom();
        }
    } catch (e) {
        // silent fail
    }
};

const startNewConversation = () => {
    conversationId.value = null;
    messages.value = [];
};

const deleteConversation = async (e, id) => {
    e.stopPropagation();
    try {
        await fetch(`${API_BASE}/conversations/${id}`, {
            method: 'DELETE',
            headers: getHeaders(),
        });
        conversations.value = conversations.value.filter(c => c.id !== id);
        if (conversationId.value === id) {
            startNewConversation();
        }
    } catch (e) {
        // silent fail
    }
};

const suggestions = [
    { title: "Gostaria de consultar um protocolo RAT", icon: DocumentTextIcon },
    { title: "Preciso ver o alerta meteorologico de hoje", icon: CloudIcon },
    { title: "Como realizar um novo cadastro de abrigo?", icon: ShieldCheckIcon },
    { title: "Verificar status da viatura operacional", icon: ExclamationTriangleIcon },
    { title: "Gerar relatorio de assistencia tecnica", icon: Squares2X2Icon },
    { title: "Consultar base de dados de voluntarios", icon: UserIcon },
];

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

watch(() => messages.value.length, scrollToBottom);
watch(() => isThinking.value, (val) => {
    if (val) scrollToBottom();
});
watch(() => props.show, async (val) => {
    if (val) {
        scrollToBottom();
        await loadConversations();
    }
});

const handleSend = async (content = null) => {
    const textToSend = content || userInput.value;
    if (!textToSend || !textToSend.trim()) return;

    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: textToSend,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });

    userInput.value = '';
    scrollToBottom();

    const aiMsgId = Date.now() + 1;
    messages.value.push({
        id: aiMsgId,
        role: 'assistant',
        content: '',
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    });

    const resolvedConvId = await ask(
        textToSend,
        (chunk) => {
            const idx = messages.value.findIndex(m => m.id === aiMsgId);
            if (idx !== -1) {
                messages.value[idx].content += chunk;
                scrollToBottom();
            }
        },
        () => {},
        conversationId.value,
    );

    if (resolvedConvId && resolvedConvId !== conversationId.value) {
        conversationId.value = resolvedConvId;
    }

    await loadConversations();
};

const close = () => {
    emit('close');
};
</script>

<template>
    <Modal :show="show" @close="close" maxWidth="5xl">
        <div class="flex h-[80vh] bg-[#F8FAFC] dark:bg-[#0b0f1a] text-slate-800 dark:text-slate-200 font-sans overflow-hidden rounded-lg">

            <!-- SIDEBAR -->
            <aside
                class="transition-all duration-300 ease-in-out border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0f172a] flex flex-col flex-shrink-0"
                :class="isSidebarOpen ? 'w-80' : 'w-0 opacity-0 overflow-hidden'"
            >
                <div class="p-4 flex flex-col gap-3">
                    <button
                        @click="startNewConversation"
                        class="flex items-center gap-2 w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors"
                    >
                        <PlusIcon class="w-4 h-4" />
                        Nova conversa
                    </button>
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Buscar historico..."
                            class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800/50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/50 transition-all outline-none"
                        />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-3 space-y-1 custom-scrollbar">
                    <div v-if="isLoadingConversations" class="flex items-center justify-center py-8">
                        <div class="w-4 h-4 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    </div>

                    <p v-else-if="filteredConversations.length === 0" class="text-xs text-center text-slate-400 py-8 px-2">
                        Nenhuma conversa encontrada
                    </p>

                    <button
                        v-for="conv in filteredConversations"
                        :key="conv.id"
                        @click="selectConversation(conv)"
                        class="w-full text-left p-3 rounded-xl group flex items-center gap-3 transition-colors"
                        :class="conversationId === conv.id
                            ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400'
                            : 'hover:bg-slate-100 dark:hover:bg-slate-800/50'"
                    >
                        <ChatBubbleLeftIcon class="w-4 h-4 flex-shrink-0 text-slate-400 group-hover:text-blue-500" />
                        <span class="text-sm truncate flex-1 text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">
                            {{ conv.title || 'Conversa sem titulo' }}
                        </span>
                        <button
                            @click="deleteConversation($event, conv.id)"
                            class="opacity-0 group-hover:opacity-100 p-1 hover:text-red-500 transition-all flex-shrink-0"
                        >
                            <TrashIcon class="w-3 h-3" />
                        </button>
                    </button>
                </div>

                <div class="p-5 border-t border-slate-200 dark:border-slate-800 space-y-3">
                    <button class="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
                        <Cog6ToothIcon class="w-4 h-4" /> Configuracoes
                    </button>
                    <button class="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
                        <QuestionMarkCircleIcon class="w-4 h-4" /> Ajuda e Suporte
                    </button>

                    <div class="mt-4 p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/20">
                        <p class="text-xs font-bold uppercase tracking-wider mb-1">Status do Sistema</p>
                        <p class="text-[10px] opacity-80 mb-3 font-medium">
                            {{ isReady ? 'IA Hibrida Ativa (WASM + Cloud)' : 'Carregando Modulos IA...' }}
                        </p>
                        <button class="w-full py-2 bg-white/20 hover:bg-white/30 backdrop-blur-md rounded-lg text-xs font-bold transition-all">
                            Ver Painel Geral
                        </button>
                    </div>
                </div>
            </aside>

            <!-- MAIN AREA -->
            <main class="flex-1 flex flex-col relative bg-white dark:bg-[#0b0f1a] min-w-0">

                <!-- HEADER -->
                <header class="h-16 flex items-center justify-between px-6 border-b border-slate-200 dark:border-slate-800 flex-shrink-0">
                    <button
                        @click="isSidebarOpen = !isSidebarOpen"
                        class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-slate-400 transition-colors"
                    >
                        <ChevronLeftIcon
                            class="w-5 h-5 transition-transform duration-300"
                            :class="{ 'rotate-180': !isSidebarOpen }"
                        />
                    </button>

                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-blue-500 bg-blue-500/10 px-2 py-1 rounded-md uppercase tracking-tighter">SDC IA 2.0</span>
                        <button @click="close" class="p-1 hover:bg-red-50 text-slate-400 hover:text-red-500 rounded-lg ml-2 transition-colors">
                            <span class="sr-only">Fechar</span>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </header>

                <!-- DYNAMIC CONTENT -->
                <div class="flex-1 overflow-y-auto custom-scrollbar" ref="messagesContainer">

                    <!-- HERO STATE (If no messages) -->
                    <div v-if="messages.length === 0" class="min-h-full flex flex-col items-center justify-center p-8 max-w-4xl mx-auto text-center">
                        <div class="mb-8 relative">
                            <div class="absolute inset-0 bg-blue-500/20 blur-3xl rounded-full scale-150 animate-pulse"></div>
                            <div class="relative w-24 h-24 bg-slate-800 rounded-2xl flex items-center justify-center shadow-2xl border border-slate-700">
                                <picture>
                                    <source srcset="/imgs/logo_dc.webp" type="image/webp" />
                                    <img src="/imgs/logo_dc.png" alt="SDC Logo" class="w-16 h-16 object-contain" />
                                </picture>
                            </div>
                        </div>

                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-4">
                            Vamos comecar com <span class="text-blue-500">sua duvida aqui</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mb-12 max-w-lg text-lg">
                            Eu sou o Assistente de Inteligencia da Defesa Civil. Como posso agilizar seus processos hoje?
                        </p>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
                            <button
                                v-for="(item, i) in suggestions"
                                :key="i"
                                @click="handleSend(item.title)"
                                class="flex items-center justify-between p-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-800/20 hover:border-blue-500/50 hover:bg-blue-50 dark:hover:bg-blue-500/5 transition-all group text-left shadow-sm"
                            >
                                <div class="flex items-center gap-4">
                                    <div class="p-2 bg-slate-100 dark:bg-slate-800 rounded-xl text-slate-500 group-hover:text-blue-500 group-hover:bg-white dark:group-hover:bg-slate-700 transition-colors">
                                        <component :is="item.icon" class="w-5 h-5" />
                                    </div>
                                    <span class="text-sm font-semibold text-slate-700 dark:text-slate-300 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                                        {{ item.title }}
                                    </span>
                                </div>
                                <ArrowRightIcon class="w-4 h-4 text-slate-300 group-hover:text-blue-500 group-hover:translate-x-1 transition-all" />
                            </button>
                        </div>
                    </div>

                    <!-- CHAT ACTIVE STATE -->
                    <div v-else class="max-w-3xl mx-auto py-10 px-4 space-y-8">
                        <div v-for="msg in messages" :key="msg.id" class="flex gap-4" :class="{ 'flex-row-reverse': msg.role === 'user' }">

                            <!-- Avatar -->
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center border shadow-sm flex-shrink-0"
                                :class="msg.role === 'user' ? 'bg-blue-600 border-blue-500 text-white' : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-blue-500 shadow-md shadow-blue-500/5'"
                            >
                                <UserIcon v-if="msg.role === 'user'" class="w-5 h-5" />
                                <CpuChipIcon v-else class="w-5 h-5" />
                            </div>

                            <!-- Message Body -->
                            <div class="flex flex-col max-w-[85%]" :class="msg.role === 'user' ? 'items-end' : 'items-start'">
                                <div
                                    class="p-5 rounded-2xl text-sm leading-relaxed shadow-sm whitespace-pre-wrap"
                                    :class="msg.role === 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-tl-none'"
                                >
                                    {{ msg.content }}
                                    <span v-if="msg.role === 'assistant' && msg.content === '' && isThinking" class="inline-block w-2 h-4 bg-blue-500 animate-pulse ml-1">|</span>
                                </div>
                                <span class="text-[10px] text-slate-400 mt-2 font-medium">{{ msg.timestamp }}</span>
                            </div>
                        </div>

                        <!-- Thinking Indicator -->
                        <div v-if="isThinking && messages[messages.length-1].role !== 'assistant'" class="flex gap-4">
                            <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-blue-500 shadow-md shadow-blue-500/5">
                                <CpuChipIcon class="w-5 h-5" />
                            </div>
                            <div class="p-5 bg-white dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 rounded-2xl rounded-tl-none flex gap-1.5 items-center">
                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                                <div class="w-1.5 h-1.5 bg-blue-500 rounded-full animate-bounce"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FOOTER / INPUT -->
                <footer class="p-6 bg-gradient-to-t from-white dark:from-[#0b0f1a] via-white dark:via-[#0b0f1a] to-transparent z-10">
                    <div class="max-w-4xl mx-auto">
                        <div v-if="error" class="mb-2 p-2 bg-red-100 text-red-700 rounded-lg text-xs">
                            {{ error }}
                        </div>
                        <div class="relative flex items-center bg-white dark:bg-[#161d2b] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-none p-1 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all">
                            <button class="p-3 text-slate-400 hover:text-blue-500 transition-colors">
                                <PaperClipIcon class="w-5 h-5" />
                            </button>

                            <textarea
                                v-model="userInput"
                                @keydown.enter.prevent="handleSend()"
                                placeholder="Escreva sua mensagem aqui ou escolha uma sugestao..."
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-slate-800 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-600 resize-none py-3 px-2 min-h-[48px] max-h-40 outline-none"
                                rows="1"
                            />

                            <button
                                @click="handleSend()"
                                :disabled="!userInput.trim() || isThinking"
                                class="p-3 rounded-xl transition-all m-1"
                                :class="userInput.trim() && !isThinking ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
                            >
                                <PaperAirplaneIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <p class="text-[10px] text-center mt-4 text-slate-400 dark:text-slate-600 font-bold uppercase tracking-widest">
                            Defesa Civil MG • Assistente Inteligente • 2024
                        </p>
                    </div>
                </footer>

            </main>
        </div>
    </Modal>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
</style>
