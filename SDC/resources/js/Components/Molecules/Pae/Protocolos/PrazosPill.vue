<template>
  <span v-if="prazoLabel" :class="pillClasses">
    {{ prazoLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  prazo: {
    type: String,
    default: 'ok', // ok|proximo|vencido
  },
  class: {
    type: String,
    default: '',
  },
});

const map = {
  proximo: { label: 'Próximo', classes: 'bg-amber-100 dark:bg-amber-500/20 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-500/20' },
  vencido: { label: 'Vencido', classes: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-300 border border-red-300 dark:border-red-500/20' },
};

const prazoLabel = computed(() => map[props.prazo]?.label || '');
const pillClasses = computed(() => {
  return [
    'inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold',
    map[props.prazo]?.classes || '',
    props.class,
  ].join(' ');
});
</script>


