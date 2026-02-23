<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import {
    ArrowLeftIcon,
    ExclamationTriangleIcon,
    ListBulletIcon,
    TicketIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const emit = defineEmits(['back', 'close']);

const activeTab = ref('new-ticket'); // new-ticket, list, emergency
const tickets = ref([]);
const loadingTickets = ref(false);

const form = useForm({
    subject: '',
    category: 'doubt',
    priority: 'low',
    description: '',
    attachment: null,
});

// Categories & Priorities
const categories = [
    { value: 'error', label: 'Erro no Sistema' },
    { value: 'doubt', label: 'Dúvida de Uso' },
    { value: 'suggestion', label: 'Sugestão' },
    { value: 'other', label: 'Outro' },
];

const priorities = [
    { value: 'low', label: 'Baixa' },
    { value: 'medium', label: 'Média' },
    { value: 'high', label: 'Alta' },
    { value: 'critical', label: 'Crítica' },
];

const setTab = (tab) => {
    activeTab.value = tab;
    if (tab === 'list') {
        fetchTickets();
    }
};

const fetchTickets = async () => {
    loadingTickets.value = true;
    try {
        const response = await axios.get(route('suporte.index'));
        tickets.value = response.data.data;
    } catch (error) {
        console.error('Erro ao buscar tickets:', error);
    } finally {
        loadingTickets.value = false;
    }
};

const submitTicket = async () => {
    try {
        await axios.post(route('suporte.store'), form.data());
        form.reset();
        activeTab.value = 'list';
        fetchTickets();
    } catch (error) {
        console.error('Erro ao enviar ticket:', error);
    }
};

const getStatusColor = (status) => {
    const colors = {
        open: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        resolved: 'bg-green-100 text-green-800',
        closed: 'bg-gray-100 text-gray-800'
    };
    return colors[status] || 'bg-gray-100';
};
</script>

<template>
    <div class="flex h-full bg-white dark:bg-slate-900 overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-50 dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700 flex flex-col">
            <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                <button @click="$emit('back')" class="flex items-center gap-2 text-slate-500 hover:text-blue-600 transition-colors mb-4">
                    <ArrowLeftIcon class="w-4 h-4" />
                    <span class="text-sm font-medium">Voltar ao Guia</span>
                </button>
                <h2 class="text-lg font-bold text-slate-800 dark:text-white flex items-center gap-2">
                    <TicketIcon class="w-6 h-6 text-blue-600" />
                    Chamados
                </h2>
            </div>
            
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <button @click="setTab('new-ticket')" :class="{'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': activeTab === 'new-ticket', 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700': activeTab !== 'new-ticket'}" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    <TicketIcon class="w-5 h-5" />
                    Abrir Chamado
                </button>

                <button @click="setTab('list')" :class="{'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': activeTab === 'list', 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700': activeTab !== 'list'}" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    <ListBulletIcon class="w-5 h-5" />
                    Meus Chamados
                </button>

                <button @click="setTab('emergency')" :class="{'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': activeTab === 'emergency', 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700': activeTab !== 'emergency'}" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                    <ExclamationTriangleIcon class="w-5 h-5" />
                    Emergência
                </button>
            </nav>
        </aside>

        <!-- Content Area -->
        <main class="flex-1 flex flex-col min-h-0 bg-white dark:bg-slate-900 relative">
            <!-- Close Button -->
            <button @click="$emit('close')" class="absolute top-4 right-4 p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors z-10">
                <XMarkIcon class="w-6 h-6" />
            </button>

            <div class="flex-1 overflow-y-auto p-8">
                
                <!-- New Ticket Tab -->
                <div v-if="activeTab === 'new-ticket'" class="max-w-2xl mx-auto pt-4">
                    <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-6 tracking-tight">Novo Chamado</h3>
                    
                    <form @submit.prevent="submitTicket" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="Assunto" />
                                <TextInput v-model="form.subject" class="w-full mt-1" placeholder="Resumo do problema" />
                            </div>
                            <div>
                                <InputLabel value="Categoria" />
                                <select v-model="form.category" class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">{{ cat.label }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel value="Prioridade" />
                                <select v-model="form.priority" class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm">
                                    <option v-for="prio in priorities" :key="prio.value" :value="prio.value">{{ prio.label }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Evidência (Opcional)" />
                                <input type="file" @change="form.attachment = $event.target.files[0]" class="w-full mt-1 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Descrição Detalhada" />
                            <textarea v-model="form.description" rows="5" class="w-full mt-1 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm" placeholder="Descreva o problema com detalhes..."></textarea>
                        </div>

                        <div class="flex justify-end pt-4">
                            <PrimaryButton :disabled="form.processing">
                                {{ form.processing ? 'Enviando...' : 'Enviar Chamado' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Ticket List Tab -->
                <div v-if="activeTab === 'list'" class="pt-4">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-black text-slate-800 dark:text-white tracking-tight">Meus Chamados</h3>
                        <button @click="fetchTickets" class="text-sm text-blue-600 hover:underline">Atualizar</button>
                    </div>

                    <div v-if="loadingTickets" class="text-center py-12 text-slate-500">
                        Carregando...
                    </div>

                    <div v-else-if="tickets.length === 0" class="text-center py-12 text-slate-500 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-dashed border-slate-300 dark:border-slate-700">
                        Nenhum chamado encontrado.
                    </div>

                    <div v-else class="space-y-4">
                        <div v-for="ticket in tickets" :key="ticket.id" class="p-5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl hover:shadow-md transition-shadow">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-lg">#{{ ticket.id }} - {{ ticket.subject }}</h4>
                                    <p class="text-sm text-slate-500 mt-1 flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold">{{ ticket.category_label }}</span>
                                        <span>•</span>
                                        <span>{{ ticket.created_at }}</span>
                                    </p>
                                </div>
                                <span :class="getStatusColor(ticket.status)" class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide">
                                    {{ ticket.status_label }}
                                </span>
                            </div>
                            <p class="mt-3 text-slate-600 dark:text-slate-300 text-sm line-clamp-2">{{ ticket.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Emergency Tab -->
                <div v-if="activeTab === 'emergency'" class="text-center py-16">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 mb-6 animate-pulse">
                        <ExclamationTriangleIcon class="w-10 h-10" />
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">Contatos de Emergência</h3>
                    <p class="text-slate-600 dark:text-slate-400 mb-8 max-w-md mx-auto">Utilize estes canais <b>apenas</b> para paralisação total do sistema ou incidentes críticos de segurança.</p>
                    
                    <div class="max-w-md mx-auto bg-slate-50 dark:bg-slate-800 rounded-2xl p-8 space-y-6 border border-slate-100 dark:border-slate-700">
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Plantão Técnico</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white text-lg">(31) 99999-9999</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-white dark:bg-slate-700/50 rounded-xl border border-slate-200 dark:border-slate-600">
                            <span class="text-slate-600 dark:text-slate-300 font-medium">Coordenador</span>
                            <span class="font-mono font-bold text-slate-900 dark:text-white text-lg">(31) 3915-0000</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</template>
