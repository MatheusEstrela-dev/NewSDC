<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  plantoes: {
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

const emit = defineEmits(['view', 'edit', 'delete']);

const getStatusClasses = (status) => {
  const s = status?.toLowerCase?.() || '';
  if (s === 'ativo') {
    return 'bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/25';
  }
  return 'bg-slate-500/15 text-slate-400 ring-1 ring-slate-500/25';
};
</script>

<template>
  <ListContainer
    title="Histórico de Plantões"
    :icon="ClockIcon"
  >
    <table class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Data</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Plantonista</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Período</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-center">Status</th>
            <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-36 min-w-36">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr
            v-for="item in plantoes"
            :key="item.id"
            class="table-row-solid transition-colors"
          >
            <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-200">{{ item.data }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-blue-600/20 flex items-center justify-center text-xs font-bold text-blue-400">
                  {{ item.avatar }}
                </div>
                <span class="text-slate-700 dark:text-slate-300">{{ item.plantonista_nome }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-slate-500 dark:text-slate-400 text-xs">{{ item.periodo }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="getStatusClasses(item.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                {{ item.status }}
              </span>
            </td>
            <td class="table-actions-cell px-4 py-3 text-right w-36 min-w-36">
              <div class="flex justify-end">
                <ActionButton
                  module="plantao"
                  resource="turnos"
                  :actions="[
                    { action: 'view',   handler: () => emit('view', item.id) },
                    { action: 'edit',   handler: () => emit('edit', item.id),   allowed: canEdit },
                    { action: 'delete', handler: () => emit('delete', item.id), allowed: canDelete },
                  ]"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!plantoes || plantoes.length === 0">
            <td colspan="5" class="p-0">
              <ListEmptyState
                title="Nenhum plantão encontrado"
                helper=""
              />
            </td>
          </tr>
        </tbody>
      </table>
  </ListContainer>
</template>
