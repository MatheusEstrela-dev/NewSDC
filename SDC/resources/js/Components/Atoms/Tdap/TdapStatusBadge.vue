<template>
  <span :class="badgeClasses">
    {{ displayLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  active: {
    type: Boolean,
    default: false,
  },
  state: {
    type: String,
    default: null,
    validator: (v) => v === null || ['vigente', 'ativo', 'ativa', 'inativo', 'inativa', 'encerrado', 'encerrada', 'rascunho', 'aprovada', 'reprovada', 'expirada'].includes(v),
  },
  label: {
    type: String,
    default: null,
  },
});

const BASE = 'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold';

const STATE_STYLES = {
  vigente: 'border border-blue-500/25 bg-blue-500/10 text-blue-700 dark:text-blue-300',
  ativo: 'border border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
  ativa: 'border border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
  inativo: 'border border-slate-500/25 bg-slate-500/10 text-slate-600 dark:text-slate-300',
  inativa: 'border border-slate-500/25 bg-slate-500/10 text-slate-600 dark:text-slate-300',
  encerrado: 'border border-slate-400/25 bg-slate-400/10 text-slate-500 dark:text-slate-400',
  encerrada: 'border border-slate-400/25 bg-slate-400/10 text-slate-500 dark:text-slate-400',
  rascunho: 'border border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-300',
  aprovada: 'border border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
  reprovada: 'border border-red-500/25 bg-red-500/10 text-red-700 dark:text-red-300',
  expirada: 'border border-amber-500/25 bg-amber-500/10 text-amber-700 dark:text-amber-300',
};

const STATE_LABELS = {
  vigente: 'Vigente',
  ativo: 'Ativo',
  ativa: 'Ativa',
  inativo: 'Inativo',
  inativa: 'Inativa',
  encerrado: 'Encerrado',
  encerrada: 'Encerrada',
  rascunho: 'Rascunho',
  aprovada: 'Aprovada',
  reprovada: 'Reprovada',
  expirada: 'Expirada',
};

const resolvedState = computed(() => {
  if (props.state) return props.state;
  return props.active ? 'ativo' : 'inativo';
});

const badgeClasses = computed(() => `${BASE} ${STATE_STYLES[resolvedState.value]}`);

const displayLabel = computed(() => {
  if (props.label) return props.label;
  return STATE_LABELS[resolvedState.value];
});
</script>
