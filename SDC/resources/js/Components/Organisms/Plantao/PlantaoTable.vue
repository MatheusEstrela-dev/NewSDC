<script setup>


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

import TableActions from '@/Components/Molecules/Table/TableActions.vue';
</script>

<template>
  <div class="bg-white dark:bg-slate-800/60 rounded-xl sm:rounded-2xl border border-slate-200 dark:border-slate-700/50 overflow-hidden backdrop-blur-sm">
    <!-- Header -->
    <div class="p-4 sm:p-6 border-b border-slate-200 dark:border-slate-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div class="flex items-center space-x-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h3 class="font-semibold text-slate-900 dark:text-white text-sm sm:text-base">Histórico de Plantões</h3>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead>
          <tr class="text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-700/50 bg-slate-50 dark:bg-slate-700/20">
            <th class="px-6 py-3.5 font-semibold text-xs uppercase tracking-wider">Data</th>
            <th class="px-6 py-3.5 font-semibold text-xs uppercase tracking-wider">Plantonista</th>
            <th class="px-6 py-3.5 font-semibold text-xs uppercase tracking-wider">Período</th>
            <th class="px-6 py-3.5 font-semibold text-xs uppercase tracking-wider text-center">Status</th>
            <th class="px-6 py-3.5 font-semibold text-xs uppercase tracking-wider text-right w-36 min-w-36">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <tr
            v-for="item in plantoes"
            :key="item.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors"
          >
            <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-200">{{ item.data }}</td>
            <td class="px-6 py-4">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full bg-blue-600/20 flex items-center justify-center text-xs font-bold text-blue-400">
                  {{ item.avatar }}
                </div>
                <span class="text-slate-700 dark:text-slate-300">{{ item.plantonista_nome }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-slate-500 dark:text-slate-400 text-xs">{{ item.periodo }}</td>
            <td class="px-6 py-4 text-center">
              <span :class="getStatusClasses(item.status)" class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider">
                {{ item.status }}
              </span>
            </td>
            <td class="px-6 py-4 text-right w-36 min-w-36">
              <div class="flex justify-end">
                <TableActions
                  :show-view="true"
                  :show-edit="canEdit"
                  :show-delete="canDelete"
                  :show-print="false"
                  :show-attachments="false"
                  @view="emit('view', item.id)"
                  @edit="emit('edit', item.id)"
                  @delete="emit('delete', item.id)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="!plantoes || plantoes.length === 0">
            <td colspan="5" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
              Nenhum plantão encontrado
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
