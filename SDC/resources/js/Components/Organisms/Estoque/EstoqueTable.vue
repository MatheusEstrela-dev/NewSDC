<template>
  <ListContainer
    title="Produtos e Lotes"
    :icon="ArchiveBoxIcon"
    subtitle="Saldo operacional com validade, endereço e regra FEFO/PVPS"
    :count="produtos.length"
  >
    <div v-if="loading" class="p-10 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
      <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando produtos...</p>
    </div>

    <table v-else class="w-full text-sm text-left">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Produto</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Lote</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Saldo</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Unidade</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Validade</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Endereço</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs">Status</th>
            <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs text-right w-32">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr
            v-for="produto in produtos"
            :key="produto.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
          >
            <td class="px-4 py-3">
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ produto.nome }}</div>
              <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ produto.sku }} / {{ produto.categoria }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-3 font-medium text-slate-700 dark:text-slate-300">
              {{ produto.lote }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-700 dark:text-slate-300">
              <div class="font-semibold">{{ produto.saldo }} {{ produto.unidade_base }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">Reserva {{ produto.reservado }}</div>
            </td>
            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ produto.unidade_label }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ formatDate(produto.validade) }}
            </td>
            <td class="whitespace-nowrap px-4 py-3 text-slate-600 dark:text-slate-300">
              {{ produto.endereco }}
            </td>
            <td class="whitespace-nowrap px-4 py-3">
              <EstoqueStatusBadge :status="produto.status" />
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  module="estoque"
                  resource="movimentacoes"
                  action="history"
                  :allowed="canMove"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Movimentar produto"
                  @click="emit('move', produto)"
                />
                <ActionButton
                  module="estoque"
                  resource="produtos"
                  action="edit"
                  :allowed="canEdit"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar produto"
                  @click="emit('edit', produto)"
                />
                <ActionButton
                  module="estoque"
                  resource="produtos"
                  action="delete"
                  :allowed="canDelete"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Excluir produto"
                  @click="emit('delete', produto)"
                />
              </div>
            </td>
          </tr>

          <tr v-if="produtos.length === 0">
            <td colspan="8" class="p-0">
              <ListEmptyState
                :icon="ArchiveBoxIcon"
                title="Nenhum produto encontrado"
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
import EstoqueStatusBadge from '@/Components/Molecules/Estoque/EstoqueStatusBadge.vue';
import ListContainer from '@/Components/Organisms/ListContainer.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { ArchiveBoxIcon } from '@heroicons/vue/24/outline';

defineProps({
  produtos: {
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
  canMove: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['edit', 'delete', 'move']);

function formatDate(value) {
  if (!value) return 'Sem validade';
  return new Date(value).toLocaleDateString('pt-BR');
}
</script>
