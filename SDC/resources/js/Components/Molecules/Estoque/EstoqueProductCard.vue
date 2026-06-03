<template>
  <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-slate-700/50 dark:bg-slate-900/60">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ produto.nome }}
        </h3>
        <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">
          {{ produto.sku }} / {{ produto.lote }}
        </p>
      </div>
      <EstoqueStatusBadge :status="produto.status" />
    </div>

    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Saldo</dt>
        <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">
          {{ produto.saldo }} {{ produto.unidade_base }}
        </dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Reservado</dt>
        <dd class="mt-1 font-semibold text-slate-900 dark:text-slate-100">
          {{ produto.reservado }} {{ produto.unidade_base }}
        </dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Validade</dt>
        <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ formatDate(produto.validade) }}</dd>
      </div>
      <div>
        <dt class="text-slate-500 dark:text-slate-400">Endereco</dt>
        <dd class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ produto.endereco }}</dd>
      </div>
      <div class="col-span-2">
        <dt class="text-slate-500 dark:text-slate-400">Unidade</dt>
        <dd class="mt-1 truncate font-medium text-slate-800 dark:text-slate-200">{{ produto.unidade_label }}</dd>
      </div>
    </dl>

    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3 dark:border-slate-800">
      <span class="text-xs text-slate-500 dark:text-slate-400">
        {{ produto.regra_saida }} / {{ produto.destino_obrigatorio }}
      </span>
      <div class="flex items-center gap-2">
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
    </div>
  </article>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import EstoqueStatusBadge from '@/Components/Molecules/Estoque/EstoqueStatusBadge.vue';

defineProps({
  produto: {
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
