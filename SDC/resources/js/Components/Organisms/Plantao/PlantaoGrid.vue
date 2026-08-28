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

const emit = defineEmits(['view', 'print', 'edit', 'delete']);


import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div
      v-for="item in plantoes"
      :key="item.id"
      class="bg-white dark:bg-slate-800/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700/50 hover:border-slate-300 dark:hover:border-slate-600 transition-all shadow-sm hover:shadow-md"
    >
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-blue-600/20 flex items-center justify-center text-sm font-bold text-blue-400 ring-2 ring-blue-600/10">
            {{ item.avatar }}
          </div>
          <div>
            <p class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-1" :title="item.plantonista_nome">
              {{ item.plantonista_nome }}
            </p>
            <p class="text-xs text-slate-500 flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              {{ item.data }}
            </p>
          </div>
        </div>
        <StatusPlantaoBadge :status="item.status_valor" :label="item.status" />
      </div>
      
      <div class="space-y-2 mb-4">
        <div class="flex justify-between items-center text-xs p-2 rounded-lg bg-slate-50 dark:bg-slate-700/30">
          <span class="text-slate-500 dark:text-slate-400">Período</span>
          <span class="font-medium text-slate-700 dark:text-slate-200">{{ item.periodo }}</span>
        </div>
      </div>

      <div class="flex justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700/50">
        <ActionButton
          module="plantao"
          resource="turnos"
          :actions="[
            { action: 'view',   handler: () => emit('view', item.id) },
            // Mesma escolha de slug do PlantaoTable.vue: ver comentario la.
            { action: 'print',  handler: () => emit('print', item.id), resource: 'passagem', aliasOverride: 'relatorio' },
            { action: 'edit',   handler: () => emit('edit', item.id),   allowed: canEdit && item.pode_editar },
            { action: 'delete', handler: () => emit('delete', item.id), allowed: canDelete },
          ]"
        />
      </div>
    </div>
    
    <div v-if="!plantoes || plantoes.length === 0" class="col-span-full p-12 text-center bg-white dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/50">
      <div class="text-slate-400 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </div>
      <p class="text-slate-500 dark:text-slate-400">Nenhum plantão encontrado</p>
    </div>
  </div>
</template>
