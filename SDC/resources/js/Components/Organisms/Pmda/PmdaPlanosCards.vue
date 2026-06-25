<template>
  <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
    <div
      v-for="plano in planos"
      :key="plano.id"
      class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
    >
      <div class="flex items-start justify-between gap-2">
        <span class="font-mono text-sm text-slate-700 dark:text-slate-300">{{ plano.protocolo ?? '—' }}</span>
        <PmdaStatusBadge :label="plano.status_label" :color-class="plano.status_color" />
      </div>
      <p class="mt-2 font-semibold text-slate-900 dark:text-slate-100">{{ plano.municipio ?? '—' }}</p>
      <p class="text-xs text-slate-500 dark:text-slate-400">Criação: {{ formatDate(plano.data) }}</p>
      <div class="mt-3 flex items-center justify-end gap-1 border-t border-slate-100 pt-3 dark:border-slate-700/50">
        <ActionButton
          action="edit" module="pmda" resource="planos"
          :allowed="canEdit" :show-label="false" size="sm"
          tooltip-text="Editar PMDA"
          @click="$emit('edit', plano.id)"
        />
        <ActionButton
          v-if="plano.pode_copiar"
          action="duplicate" module="pmda" resource="planos"
          :allowed="canCopiar" :show-label="false" size="sm"
          tooltip-text="Criar cópia"
          @click="$emit('copiar', plano.id)"
        />
      </div>
    </div>

    <div
      v-if="!planos.length"
      class="col-span-full rounded-lg border border-dashed border-slate-300 p-10 text-center text-sm text-slate-500 dark:border-slate-700"
    >
      Nenhum PMDA encontrado.
    </div>
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';

defineProps({
  planos: { type: Array, default: () => [] },
  canEdit: { type: Boolean, default: false },
  canCopiar: { type: Boolean, default: false },
});

defineEmits(['edit', 'copiar']);

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
}
</script>
