<template>
  <component :is="tag" :class="textClasses">
    <slot />
  </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  tag: {
    type: String,
    default: 'p',
    validator: (value) => ['p', 'span', 'div'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg'].includes(value),
  },
  color: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'muted', 'primary', 'success', 'warning', 'danger'].includes(value),
  },
  weight: {
    type: String,
    default: 'normal',
    validator: (value) => ['normal', 'medium', 'semibold', 'bold'].includes(value),
  },
});

const sizeClasses = {
  xs: 'text-xs',
  sm: 'text-sm',
  md: 'text-base',
  lg: 'text-lg',
};

const colorClasses = {
  default: 'text-slate-300 dark:text-slate-300 text-slate-700',
  muted: 'text-slate-500 dark:text-slate-500 text-slate-500',
  primary: 'text-blue-400 dark:text-blue-400 text-blue-600',
  success: 'text-emerald-400 dark:text-emerald-400 text-emerald-600',
  warning: 'text-amber-400 dark:text-amber-400 text-amber-600',
  danger: 'text-red-400 dark:text-red-400 text-red-600',
};

const weightClasses = {
  normal: 'font-normal',
  medium: 'font-medium',
  semibold: 'font-semibold',
  bold: 'font-bold',
};

const textClasses = computed(() => {
  return [
    sizeClasses[props.size],
    colorClasses[props.color],
    weightClasses[props.weight],
  ].join(' ');
});
</script>

