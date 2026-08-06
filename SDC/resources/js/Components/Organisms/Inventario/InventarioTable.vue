<template>
  <ListContainer
    title="Equipamentos"
    :icon="ArchiveBoxIcon"
    subtitle="Lista inicial do acervo patrimonial"
    :count="equipamentos.length"
  >
    <div v-if="loading" class="p-10 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
      <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando equipamentos...</p>
    </div>

    <table v-else class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Equipamento</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Patrimônio</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Categoria</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Responsável</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Situação</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Última movimentação</th>
            <th class="table-actions-head px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-28">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr
            v-for="equipamento in equipamentos"
            :key="equipamento.id"
            class="table-row-solid transition-colors"
          >
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ equipamento.nome }}</div>
              <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ equipamento.diretoria }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-slate-300">
              {{ equipamento.patrimonio }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ equipamento.categoria }}
            </td>
            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ equipamento.responsavel }}
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <InventarioStatusBadge :status="equipamento.situacao" />
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ formatDate(equipamento.ultima_movimentacao) }}
            </td>
            <td class="table-actions-cell px-4 py-3">
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
            <td colspan="7" class="p-0">
              <ListEmptyState
                :icon="ArchiveBoxIcon"
                title="Nenhum equipamento encontrado"
                helper="Ajuste os filtros para ampliar a consulta."
              />
            </td>
          </tr>
        </tbody>
      </table>
  </ListContainer>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import InventarioStatusBadge from '@/Components/Molecules/Inventario/InventarioStatusBadge.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
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
