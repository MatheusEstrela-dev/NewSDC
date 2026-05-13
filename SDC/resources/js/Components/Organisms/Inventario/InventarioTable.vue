<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Equipamentos</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Lista inicial do acervo patrimonial</p>
        </div>
        <span class="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:border-blue-500/25 dark:bg-blue-500/15 dark:text-blue-300">
          {{ equipamentos.length }}
        </span>
      </div>
    </div>

    <div v-if="loading" class="p-10 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
      <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando equipamentos...</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3 text-left">Equipamento</th>
            <th class="px-4 py-3 text-left">Patrimonio</th>
            <th class="px-4 py-3 text-left">Categoria</th>
            <th class="px-4 py-3 text-left">Responsavel</th>
            <th class="px-4 py-3 text-left">Situacao</th>
            <th class="px-4 py-3 text-left">Ultima movimentacao</th>
            <th class="w-28 px-4 py-3 text-right">Acoes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr
            v-for="equipamento in equipamentos"
            :key="equipamento.id"
            class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
          >
            <td class="px-4 py-4">
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ equipamento.nome }}</div>
              <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ equipamento.diretoria }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-700 dark:text-slate-300">
              {{ equipamento.patrimonio }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ equipamento.categoria }}
            </td>
            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ equipamento.responsavel }}
            </td>
            <td class="whitespace-nowrap px-4 py-4">
              <InventarioStatusBadge :status="equipamento.situacao" />
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ formatDate(equipamento.ultima_movimentacao) }}
            </td>
            <td class="px-4 py-4">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  module="inventario"
                  resource="equipamentos"
                  action="edit"
                  :allowed="canEdit"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar equipamento"
                  @click="emit('edit', equipamento)"
                />
                <ActionButton
                  module="inventario"
                  resource="equipamentos"
                  action="delete"
                  :allowed="canDelete"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Excluir equipamento"
                  @click="emit('delete', equipamento)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="equipamentos.length === 0">
            <td colspan="7" class="px-4 py-10 text-center">
              <ArchiveBoxIcon class="mx-auto h-12 w-12 text-slate-400" />
              <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum equipamento encontrado</p>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros para ampliar a consulta.</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import InventarioStatusBadge from '@/Components/Molecules/Inventario/InventarioStatusBadge.vue';
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

const emit = defineEmits(['edit', 'delete']);

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('pt-BR');
}
</script>
