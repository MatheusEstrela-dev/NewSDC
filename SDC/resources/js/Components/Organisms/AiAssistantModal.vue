<script setup>
import { ref, nextTick, watch, onMounted, onUnmounted } from 'vue';
import Modal from '@/Components/Modal.vue';
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
    ShieldCheckIcon 
} from '@heroicons/vue/24/outline'; // Checking imports, 24/outline is standard for Heroicons v2

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const isSidebarOpen = ref(true);
const userInput = ref('');
const isTyping = ref(false);
const messagesContainer = ref(null);
const messages = ref([]);

// Mock History Items
const historyItems = ref([
    "Análise de enchente Venda Nova",
    "Relatório de danos Januária",
    "Protocolo #9928 - Pendente",
    "Consulta meteorológica Sul",
    "Dúvida sobre formulário RAT",
    "Ajuda humanitária Juiz de Fora"
]);

const suggestions = [
    { title: "Gostaria de consultar um protocolo RAT", icon: DocumentTextIcon },
    { title: "Preciso ver o alerta meteorológico de hoje", icon: CloudIcon },
    { title: "Como realizar um novo cadastro de abrigo?", icon: ShieldCheckIcon },
    { title: "Verificar status da viatura operacional", icon: ExclamationTriangleIcon },
    { title: "Gerar relatório de assistência técnica", icon: Squares2X2Icon },
    { title: "Consultar base de dados de voluntários", icon: UserIcon },
];

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

watch(() => messages.value.length, scrollToBottom);
watch(() => props.show, (val) => {
    if (val) {
        scrollToBottom();
        // Reset state slightly on open if desired, or keep history
    }
});

const handleSend = (content = null) => {
    const textToSend = content || userInput.value;
    if (!textToSend || !textToSend.trim()) return;

    // User Message
    messages.value.push({
        id: Date.now(),
        role: 'user',
        content: textToSend,
        timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });

    userInput.value = '';
    isTyping.value = true;
    scrollToBottom();

    // AI Simulation
    setTimeout(() => {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            content: `Certo! Analisando sua solicitação sobre "${textToSend}". O sistema SDC está processando os dados geográficos e meteorológicos em tempo real para fornecer a melhor resposta.`,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
        isTyping.value = false;
        scrollToBottom();
    }, 1200);
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
                <div class="p-5 flex flex-col gap-4">
                    <div class="relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                        <input 
                            type="text" 
                            placeholder="Buscar histórico..." 
                            class="w-full pl-10 pr-4 py-2 bg-slate-100 dark:bg-slate-800/50 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500/50 transition-all outline-none"
                        />
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto px-3 space-y-1 custom-scrollbar">
                    <button 
                        v-for="(text, i) in historyItems" 
                        :key="i" 
                        class="w-full text-left p-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800/50 group flex items-center gap-3 transition-colors"
                    >
                        <ChatBubbleLeftIcon class="w-4 h-4 text-slate-400 group-hover:text-blue-500" />
                        <span class="text-sm truncate text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-white">
                            {{ text }}
                        </span>
                    </button>
                </div>

                <div class="p-5 border-t border-slate-200 dark:border-slate-800 space-y-3">
                    <button class="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
                        <Cog6ToothIcon class="w-4 h-4" /> Configurações
                    </button>
                    <button class="flex items-center gap-3 w-full p-2 text-slate-500 hover:text-blue-500 text-sm transition-colors">
                        <QuestionMarkCircleIcon class="w-4 h-4" /> Ajuda e Suporte
                    </button>
                    
                    <div class="mt-4 p-4 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 text-white shadow-lg shadow-blue-500/20">
                        <p class="text-xs font-bold uppercase tracking-wider mb-1">Status do Sistema</p>
                        <p class="text-[10px] opacity-80 mb-3 font-medium">Você está operando no módulo avançado da Defesa Civil.</p>
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
                            <!-- Simple Logo Placeholder or SVG -->
                            <div class="relative w-24 h-24 bg-slate-800 rounded-2xl flex items-center justify-center shadow-2xl border border-slate-700">
                                <img src="/imgs/logo_dc.png" alt="SDC Logo" class="w-16 h-16 object-contain" />
                            </div>
                        </div>

                        <h1 class="text-3xl md:text-4xl font-extrabold text-slate-900 dark:text-white mb-4">
                            Vamos começar com <span class="text-blue-500">sua dúvida aqui</span>
                        </h1>
                        <p class="text-slate-500 dark:text-slate-400 mb-12 max-w-lg text-lg">
                            Eu sou o Assistente de Inteligência da Defesa Civil. Como posso agilizar seus processos hoje?
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
                                    class="p-5 rounded-2xl text-sm leading-relaxed shadow-sm"
                                    :class="msg.role === 'user' ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 text-slate-800 dark:text-slate-200 rounded-tl-none'"
                                >
                                    {{ msg.content }}
                                </div>
                                <span class="text-[10px] text-slate-400 mt-2 font-medium">{{ msg.timestamp }}</span>
                            </div>
                        </div>

                        <!-- Typing Indicator -->
                        <div v-if="isTyping" class="flex gap-4">
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
                        <div class="relative flex items-center bg-white dark:bg-[#161d2b] border border-slate-200 dark:border-slate-800 rounded-2xl shadow-xl shadow-slate-200/20 dark:shadow-none p-1 focus-within:ring-2 focus-within:ring-blue-500/20 transition-all">
                            <button class="p-3 text-slate-400 hover:text-blue-500 transition-colors">
                                <PaperClipIcon class="w-5 h-5" />
                            </button>
                            
                            <textarea 
                                v-model="userInput"
                                @keydown.enter.prevent="handleSend()"
                                placeholder="Escreva sua mensagem aqui ou escolha uma sugestão..."
                                class="flex-1 bg-transparent border-none focus:ring-0 text-sm text-slate-800 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-600 resize-none py-3 px-2 min-h-[48px] max-h-40 outline-none"
                                rows="1"
                            />
                            
                            <button 
                                @click="handleSend()"
                                :disabled="!userInput.trim()"
                                class="p-3 rounded-xl transition-all m-1"
                                :class="userInput.trim() ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-400'"
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
