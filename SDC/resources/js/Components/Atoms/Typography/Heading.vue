<template>
  <component :is="tag" :class="headingClasses">
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  level: {
    type: Number,
    default: 1,
    validator: (value) => [1, 2, 3, 4, 5, 6].includes(value),
  },
  color: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'muted', 'white'].includes(value),
  },
});

const tag = computed(() => `h${props.level}`);

const sizeClasses = {
  1: 'text-3xl font-bold',
  2: 'text-2xl font-bold',
  3: 'text-xl font-semibold',
  4: 'text-lg font-semibold',
  5: 'text-base font-semibold',
  6: 'text-sm font-semibold',
};

const colorClasses = {
  default: 'text-slate-200',
  primary: 'text-blue-400',
  muted: 'text-slate-400',
  white: 'text-white',
};

const headingClasses = computed(() => {
  return [
    sizeClasses[props.level],
    colorClasses[props.color],
  ].join(' ');
});
</script>

