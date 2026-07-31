<template>
  <div class="inline-flex gap-1 items-center">
    <span :class="ativoClasses">
      {{ ativo ? 'Ativo' : 'Inativo' }}
    </span>
    <span v-if="estaAprovado" :class="aprovadoClasses">
      Aprovado
    </span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  ativo: { type: Boolean, default: false },
  estaAprovado: { type: Boolean, default: false },
});

const baseClasses = 'px-2.5 py-1 sm:px-3 sm:py-1.5 rounded-full text-xs font-semibold inline-block whitespace-nowrap';

const ativoClasses = computed(() => [
  baseClasses,
  props.ativo
    ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/30'
    : 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-500/30',
].join(' '));

const aprovadoClasses = computed(() => [
  baseClasses,
  'bg-blue-100 dark:bg-blue-500/20 text-blue-700 dark:text-blue-300 border border-blue-300 dark:border-blue-500/30',
].join(' '));
</script>
