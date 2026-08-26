<script setup>
import { computed } from 'vue';

const props = defineProps({
  valor: {
    type: Number,
    default: null,
  },
});

const formatado = computed(() =>
  props.valor === null || props.valor === undefined
    ? '--'
    : new Intl.NumberFormat('pt-BR').format(props.valor)
);
</script>

<template>
  <span
    class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-200"
  >
    {{ formatado }} km
  </span>
</template>
