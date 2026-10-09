<template>
  <div role="status" :class="['rounded-lg border p-3 text-sm', CLASSES[tom] ?? CLASSES.info]">
    <slot />
  </div>
</template>

<script setup>
defineProps({
  tom: {
    type: String,
    default: 'info',
    validator: (valor) => ['info', 'aviso', 'erro', 'neutro'].includes(valor),
  },
});

// Classes literais: o Tailwind so enxerga o que esta escrito por extenso.
const CLASSES = {
  info: 'border-blue-300 bg-blue-50 text-blue-900 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
  aviso: 'border-amber-300 bg-amber-50 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
  erro: 'border-red-300 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-200',
  neutro: 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
};
</script>
