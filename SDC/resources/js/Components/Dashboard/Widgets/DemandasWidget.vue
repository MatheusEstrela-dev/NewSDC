<template>
  <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 flex flex-col h-full min-h-[350px]">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center bg-slate-50 dark:bg-slate-800/50 rounded-t-xl">
      <h3 class="font-bold text-slate-800 dark:text-slate-200 flex items-center gap-2">
        <ClipboardDocumentListIcon class="w-5 h-5 text-indigo-500" />
        Demandas Recentes
      </h3>
      <Link :href="route('demandas.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 dark:text-indigo-400 px-3 py-1.5 rounded-full transition">
        Ver Todas
      </Link>
    </div>
    
    <div class="flex-1 overflow-y-auto p-0">
      <div v-if="loading" class="flex justify-center items-center h-full text-slate-400 py-12">
         <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-500"></div>
      </div>
      
      <div v-else-if="!demandas || demandas.length === 0" class="flex flex-col items-center justify-center h-full text-slate-500 p-8 text-center pt-12">
        <CheckCircleIcon class="w-12 h-12 text-slate-300 dark:text-slate-600 mb-3" />
        <p class="font-medium text-slate-600 dark:text-slate-400">Nenhuma demanda pendente!</p>
        <p class="text-xs mt-1 text-slate-400 dark:text-slate-500">Tudo limpo por aqui.</p>
      </div>
      
      <div v-else class="divide-y divide-slate-100 dark:divide-slate-800">
        <Link 
          v-for="demanda in demandas" 
          :key="demanda.id" 
          :href="route('demandas.show', demanda.id)"
          class="block p-4 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition cursor-pointer"
        >
          <div class="flex justify-between items-start mb-2 gap-2">
            <span class="text-xs font-mono font-medium text-slate-500 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">{{ demanda.protocolo }}</span>
            <DemandaStatusBadge :status="demanda.status">{{ demanda.status_label || demanda.status }}</DemandaStatusBadge>
          </div>
          <h4 class="text-sm font-semibold text-slate-900 dark:text-slate-100 line-clamp-1 mb-2">{{ demanda.titulo }}</h4>
          <div class="flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-1">
              <ClockIcon class="w-3.5 h-3.5" />
              <span>{{ formatDate(demanda.created_at) }}</span>
            </div>
            <DemandaPrioridadeBadge :prioridade="demanda.prioridade">{{ demanda.prioridade_label || demanda.prioridade }}</DemandaPrioridadeBadge>
          </div>
        </Link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ClipboardDocumentListIcon, ClockIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';

const props = defineProps({
  initialData: {
    type: Array,
    default: null
  }
});

const demandas = ref(props.initialData || []);
const loading = ref(!props.initialData);

onMounted(async () => {
  if (!props.initialData) {
    try {
      // Mock carregamento rápido até integrarmos rota específica da API do Dashboard
      setTimeout(() => { loading.value = false; }, 400);
    } catch (e) {
      loading.value = false;
    }
  }
});

const formatDate = (date) => new Date(date).toLocaleDateString('pt-BR');
</script>
