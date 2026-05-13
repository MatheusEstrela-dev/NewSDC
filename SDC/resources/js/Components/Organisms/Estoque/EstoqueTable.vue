<template>
  <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
      <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
          <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Produtos e Lotes</h3>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Saldo operacional com validade, endereco e regra FEFO/PVPS</p>
        </div>
        <span class="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:border-blue-500/25 dark:bg-blue-500/15 dark:text-blue-300">
          {{ produtos.length }}
        </span>
      </div>
    </div>

    <div v-if="loading" class="p-10 text-center">
      <div class="mx-auto h-8 w-8 animate-spin rounded-full border-b-2 border-blue-600"></div>
      <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">Carregando produtos...</p>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
          <tr>
            <th class="px-4 py-3 text-left">Produto</th>
            <th class="px-4 py-3 text-left">Lote</th>
            <th class="px-4 py-3 text-left">Saldo</th>
            <th class="px-4 py-3 text-left">Unidade</th>
            <th class="px-4 py-3 text-left">Validade</th>
            <th class="px-4 py-3 text-left">Endereco</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="w-32 px-4 py-3 text-right">Acoes</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
          <tr
            v-for="produto in produtos"
            :key="produto.id"
            class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
          >
            <td class="px-4 py-4">
              <div class="font-semibold text-slate-900 dark:text-slate-100">{{ produto.nome }}</div>
              <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ produto.sku }} / {{ produto.categoria }}</div>
            </td>
            <td class="whitespace-nowrap px-4 py-4 font-medium text-slate-700 dark:text-slate-300">
              {{ produto.lote }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-slate-700 dark:text-slate-300">
              <div class="font-semibold">{{ produto.saldo }} {{ produto.unidade_base }}</div>
              <div class="text-xs text-slate-500 dark:text-slate-400">Reserva {{ produto.reservado }}</div>
            </td>
            <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ produto.unidade_label }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ formatDate(produto.validade) }}
            </td>
            <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">
              {{ produto.endereco }}
            </td>
            <td class="whitespace-nowrap px-4 py-4">
              <EstoqueStatusBadge :status="produto.status" />
            </td>
            <td class="px-4 py-4">
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
            <td colspan="8" class="px-4 py-10 text-center">
              <ArchiveBoxIcon class="mx-auto h-12 w-12 text-slate-400" />
              <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum produto encontrado</p>
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
import EstoqueStatusBadge from '@/Components/Molecules/Estoque/EstoqueStatusBadge.vue';
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
