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
    </div>
  </article>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import InventarioStatusBadge from '@/Components/Molecules/Inventario/InventarioStatusBadge.vue';

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

const emit = defineEmits(['edit', 'delete']);

function formatDate(value) {
  if (!value) return '-';
  return new Date(value).toLocaleDateString('pt-BR');
}
</script>
