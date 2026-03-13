<script setup>
import { computed } from 'vue';
import StatusBadge from '@/Components/Molecules/Decretacoes/StatusBadge.vue';
import TipoProcessoBadge from '@/Components/Molecules/Decretacoes/TipoProcessoBadge.vue';
import {
  CalendarIcon,
  BuildingOfficeIcon,
  UserIcon,
  DocumentTextIcon,
  MapPinIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
});

function formatDate(date) {
  if (!date) return 'N/A';
  return new Date(date).toLocaleDateString('pt-BR');
}

const municipiosFormatted = computed(() => {
  if (!props.processo?.municipios?.length) return [];
  return props.processo.municipios.map((m) => ({
    id: m.id,
    nome: m.nome,
    protocolo: m.protocolo || m.n_protocolo_fide || 'N/A',
  }));
});
</script>

<template>
  <div class="space-y-6">
    <!-- Dados Basicos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Data de Entrada -->
      <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <CalendarIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
          <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Data de Entrada</span>
        </div>
        <p class="text-lg font-semibold text-slate-800 dark:text-white">{{ formatDate(processo.data_entrada) }}</p>
      </div>

      <!-- Tipo de Processo -->
      <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <BuildingOfficeIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
          <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Tipo de Processo</span>
        </div>
        <TipoProcessoBadge :tipo="processo.processo" />
      </div>
    </div>

    <!-- Municipios Afetados -->
    <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
      <div class="flex items-center gap-3 mb-4">
        <MapPinIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Municipio(s) Afetado(s)</span>
      </div>
      <ul v-if="municipiosFormatted.length" class="space-y-2 max-h-48 overflow-y-auto">
        <li
          v-for="municipio in municipiosFormatted"
          :key="municipio.id"
          class="flex items-center gap-2 text-slate-700 dark:text-slate-200"
        >
          <span class="w-1.5 h-1.5 bg-blue-500 dark:bg-blue-400 rounded-full flex-shrink-0"></span>
          <span class="font-medium">{{ municipio.nome }}</span>
          <span class="text-slate-500 dark:text-slate-400 text-sm">({{ municipio.protocolo }})</span>
        </li>
      </ul>
      <p v-else class="text-slate-500 dark:text-slate-400 text-sm">Nenhum municipio registrado</p>
    </div>

    <!-- Status e Analista -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Status do Processo -->
      <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <DocumentTextIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
          <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Status do Processo</span>
        </div>
        <StatusBadge :status="processo.status" />
      </div>

      <!-- Analista Responsavel -->
      <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
        <div class="flex items-center gap-3 mb-2">
          <UserIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
          <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Analista Responsavel</span>
        </div>
        <p class="text-lg font-semibold text-slate-800 dark:text-white">{{ processo.analista || 'N/A' }}</p>
      </div>
    </div>

    <!-- Processo SEI -->
    <div class="p-4 bg-white dark:bg-slate-800/50 rounded-xl border border-slate-200 dark:border-slate-700/50 shadow-sm">
      <div class="flex items-center gap-3 mb-2">
        <DocumentTextIcon class="w-5 h-5 text-slate-500 dark:text-slate-400" />
        <span class="text-sm font-medium text-slate-500 dark:text-slate-400">N Processo Inserido no SEI</span>
      </div>
      <p class="text-lg font-semibold text-slate-800 dark:text-white font-mono">{{ processo.processo_inserido_sei || processo.n_processo_sei || 'N/A' }}</p>
    </div>
  </div>
</template>
