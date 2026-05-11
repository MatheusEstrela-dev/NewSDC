<template>
  <div class="space-y-4">
    <div v-if="loading" class="rounded-lg border border-slate-200 bg-white p-10 text-center dark:border-slate-700/50 dark:bg-slate-900/60">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
      <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando equipamentos...</p>
    </div>

    <div v-else-if="equipamentos.length === 0" class="rounded-lg border border-slate-200 bg-white p-10 text-center dark:border-slate-700/50 dark:bg-slate-900/60">
      <ArchiveBoxIcon class="mx-auto h-12 w-12 text-slate-400" />
      <h3 class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum equipamento encontrado</h3>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros para ampliar a consulta.</p>
    </div>

    <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
      <InventarioEquipmentCard
        v-for="equipamento in equipamentos"
        :key="equipamento.id"
        :equipamento="equipamento"
        :can-edit="canEdit"
        :can-delete="canDelete"
      />
    </div>
  </div>
</template>

<script setup>
import InventarioEquipmentCard from '@/Components/Molecules/Inventario/InventarioEquipmentCard.vue';
import { ArchiveBoxIcon } from '@heroicons/vue/24/outline';

defineProps({
  equipamentos: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
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
</script>
