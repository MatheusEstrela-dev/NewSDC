<script setup>
import { computed } from 'vue';
import Modal from '@/Components/Modal.vue';
import { useTabs } from '@/composables/core/useTabs';
import {
  InformationCircleIcon,
  DocumentTextIcon,
  ChartBarIcon,
  LifebuoyIcon,
  XMarkIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline';

import TabInformacoes from './tabs/TabInformacoes.vue';
import TabDadosDecreto from './tabs/TabDadosDecreto.vue';
import TabTotaisDesastres from './tabs/TabTotaisDesastres.vue';
import TabPedidoAH from './tabs/TabPedidoAH.vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  processo: {
    type: Object,
    default: null,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['close']);

const { activeTab, setActiveTab, getTabClass } = useTabs(1);

const tabs = computed(() => [
  { id: 1, label: 'Informacoes Basicas', icon: InformationCircleIcon },
  { id: 2, label: 'Dados do Decreto', icon: DocumentTextIcon },
  { id: 3, label: 'Totais de Desastres', icon: ChartBarIcon },
  { id: 4, label: 'Pedido AH', icon: LifebuoyIcon, badge: props.processo?.pedidos_ah?.length || null },
]);

const tipoReconhecimentoBadge = computed(() => {
  const tipo = props.processo?.tipo_reconhecimento;
  const config = {
    SE: { label: 'SE', class: 'bg-blue-500/20 text-blue-300 border-blue-500/30' },
    EE: { label: 'EE', class: 'bg-amber-500/20 text-amber-300 border-amber-500/30' },
    CP: { label: 'CP', class: 'bg-red-500/20 text-red-300 border-red-500/30' },
  };
  return config[tipo] || config.SE;
});

function handleClose() {
  emit('close');
}
</script>

<template>
  <Modal :show="show" max-width="5xl" @close="handleClose">
    <div v-if="processo" class="flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="bg-gradient-to-r from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900 px-6 py-4 border-b border-slate-200 dark:border-slate-700/50">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-amber-100 dark:bg-slate-700/50 rounded-lg">
              <ExclamationTriangleIcon class="w-6 h-6 text-amber-500 dark:text-amber-400" />
            </div>
            <div>
              <h2 class="text-lg font-semibold text-slate-800 dark:text-white">Detalhes da Decretacao</h2>
            </div>
          </div>
          <button
            type="button"
            class="p-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-700/50 rounded-lg transition-colors"
            @click="handleClose"
          >
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Tipo Desastre -->
        <div class="mt-4 p-4 bg-white dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 shadow-sm">
          <div class="flex items-center gap-3">
            <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0" />
            <span class="text-slate-800 dark:text-white font-medium">{{ processo.tipo_desastre?.nome || 'N/A' }}</span>
            <span
              class="px-2.5 py-1 text-xs font-bold rounded-md border"
              :class="tipoReconhecimentoBadge.class"
            >
              {{ tipoReconhecimentoBadge.label }}
            </span>
          </div>
          <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3 text-sm">
            <div>
              <span class="text-slate-500 dark:text-slate-400">COBRADE:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.cobrade || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400">Categoria:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.categoria || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400">Grupo:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.grupo || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400">Subgrupo:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.subgrupo || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400">Tipo:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.tipo || 'N/A' }}</span>
            </div>
            <div>
              <span class="text-slate-500 dark:text-slate-400">Subtipo:</span>
              <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre?.subtipo || 'N/A' }}</span>
            </div>
          </div>
          <div v-if="processo.tipo_desastre?.definicao" class="mt-2 text-sm">
            <span class="text-slate-500 dark:text-slate-400">Definicao:</span>
            <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre.definicao }}</span>
          </div>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <nav class="flex items-center gap-1 px-6 py-2 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700/50 overflow-x-auto">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-lg transition-all whitespace-nowrap"
          :class="activeTab === tab.id
            ? 'bg-blue-100 dark:bg-blue-500/20 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-500/30'
            : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700/50'"
          @click="setActiveTab(tab.id)"
        >
          <component :is="tab.icon" class="w-4 h-4" />
          <span class="hidden sm:inline">{{ tab.label }}</span>
          <span
            v-if="tab.badge"
            class="px-1.5 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-500/30 text-blue-600 dark:text-blue-300 rounded"
          >
            {{ tab.badge }}
          </span>
        </button>
      </nav>

      <!-- Tab Content -->
      <div class="flex-1 overflow-y-auto p-6 bg-slate-50 dark:bg-slate-900/50">
        <Transition
          mode="out-in"
          enter-active-class="transition-opacity duration-200"
          enter-from-class="opacity-0"
          enter-to-class="opacity-100"
          leave-active-class="transition-opacity duration-150"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <TabInformacoes v-if="activeTab === 1" :processo="processo" />
          <TabDadosDecreto v-else-if="activeTab === 2" :processo="processo" />
          <TabTotaisDesastres v-else-if="activeTab === 3" :processo="processo" />
          <TabPedidoAH v-else-if="activeTab === 4" :processo="processo" />
        </Transition>
      </div>

    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="p-12 text-center">
      <div class="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
      <p class="mt-4 text-slate-400">Carregando dados...</p>
    </div>
  </Modal>
</template>
