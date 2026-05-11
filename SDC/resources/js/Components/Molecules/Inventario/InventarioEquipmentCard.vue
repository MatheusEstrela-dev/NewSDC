<template>
  <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ equipamento.nome }}
        </h3>
        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
          {{ equipamento.patrimonio }}
        </p>
      </div>
      <InventarioStatusBadge :status="equipamento.situacao" />
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Categoria</dt>
        <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ equipamento.categoria }}</dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Diretoria</dt>
        <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ equipamento.diretoria }}</dd>
      </div>
      <div class="col-span-2">
        <dt class="text-slate-500 dark:text-slate-400">Responsavel</dt>
        <dd class="mt-1 truncate font-medium text-slate-800 dark:text-slate-200">{{ equipamento.responsavel }}</dd>
      </div>
    </dl>

    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
      <span class="text-xs text-slate-500 dark:text-slate-400">
        Movimentado em {{ formatDate(equipamento.ultima_movimentacao) }}
      </span>
      <div class="flex items-center gap-2">
        <button
          v-if="canEdit"
          type="button"
          class="rounded-md p-1.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
          title="Editar"
        >
          <PencilSquareIcon class="h-4 w-4" />
        </button>
        <button
          v-if="canDelete"
          type="button"
          class="rounded-md p-1.5 text-slate-500 transition hover:bg-red-50 hover:text-red-600 dark:text-slate-400 dark:hover:bg-red-500/10 dark:hover:text-red-300"
          title="Excluir"
        >
          <TrashIcon class="h-4 w-4" />
        </button>
      </div>
    </div>
  </article>
</template>

<script setup>
import InventarioStatusBadge from '@/Components/Molecules/Inventario/InventarioStatusBadge.vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineProps({
  equipamento: {
    type: Object,
    required: true,
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

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('pt-BR');
}
</script>
