<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CombustivelGauge from '@/Components/Atoms/Plantao/CombustivelGauge.vue';
import HodometroBadge from '@/Components/Atoms/Plantao/HodometroBadge.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

defineProps({
  viaturas: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['edit', 'delete']);

// Cor por status literal no .vue: Tailwind nao escaneia app/**/*.php, entao o
// backend so manda o valor cru (status_valor) e o mapa fica aqui.
const CORES_STATUS = {
  DISPONIVEL: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
  EM_TRANSITO: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
  MANUTENCAO: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
  CEDIDA: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300',
  INDISPONIVEL: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
};

const getStatusClasses = (statusValor) => CORES_STATUS[statusValor] ?? CORES_STATUS.INDISPONIVEL;
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div
      v-for="item in viaturas"
      :key="item.id"
      class="bg-white dark:bg-slate-800/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700/50 hover:border-slate-300 dark:hover:border-slate-600 transition-all shadow-sm hover:shadow-md"
    >
      <div class="flex items-start justify-between mb-3">
        <div>
          <p class="text-sm font-bold text-slate-900 dark:text-white">
            {{ item.prefixo }}
            <span class="ml-1 font-mono text-xs font-normal text-slate-500">{{ item.placa }}</span>
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1" :title="item.modelo">
            {{ item.modelo }}<span v-if="item.marca"> ({{ item.marca }})</span>
          </p>
        </div>
        <span :class="getStatusClasses(item.status_valor)" class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
          {{ item.status }}
        </span>
      </div>

      <div class="flex items-center justify-between gap-3 mb-4 p-2 rounded-lg bg-slate-50 dark:bg-slate-700/30">
        <div class="flex flex-col gap-1 text-xs">
          <span class="text-slate-500 dark:text-slate-400">Localizacao</span>
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ item.localizacao }}</span>
          <span class="text-slate-500 dark:text-slate-400 mt-2">Hodometro</span>
          <HodometroBadge :valor="item.hodometro" />
          <span class="text-slate-500 dark:text-slate-400 mt-2">Ultimo condutor</span>
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ item.ultimo_condutor_nome ?? '--' }}</span>
        </div>

        <CombustivelGauge
          :percentual="item.combustivel_percentual"
          :label="item.combustivel_label ?? ''"
        />
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700/50">
        <ActionButton
          module="plantao"
          resource="viaturas"
          :actions="[
            { action: 'edit',   handler: () => emit('edit', item.id),   allowed: canEdit },
            { action: 'delete', handler: () => emit('delete', item.id), allowed: canDelete },
          ]"
        />
      </div>
    </div>

    <div v-if="!viaturas || viaturas.length === 0" class="col-span-full">
      <ListEmptyState
        title="Nenhuma viatura encontrada"
        helper="Ajuste os filtros ou cadastre uma nova viatura."
      />
    </div>
  </div>
</template>
