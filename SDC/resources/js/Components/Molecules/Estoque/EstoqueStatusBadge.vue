<template>
  <span :class="badgeClasses">
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  status: {
    type: String,
    default: 'normal',
  },
});

const variants = {
  normal: {
    label: 'Normal',
    classes: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/25 dark:bg-emerald-500/15 dark:text-emerald-300',
  },
  atencao: {
    label: 'Atencao',
    classes: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/25 dark:bg-amber-500/15 dark:text-amber-300',
  },
  critico: {
    label: 'Critico',
    classes: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/25 dark:bg-red-500/15 dark:text-red-300',
  },
  quarentena: {
    label: 'Quarentena',
    classes: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/25 dark:bg-violet-500/15 dark:text-violet-300',
  },
};

const config = computed(() => variants[props.status] || variants.normal);
const label = computed(() => config.value.label);
const badgeClasses = computed(() => [
  'inline-flex items-center rounded-md border px-2 py-1 text-xs font-semibold',
  config.value.classes,
]);
</script>
