<template>
  <span :class="badgeClasses">
    <slot />
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['info', 'success', 'warning', 'danger', 'default'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  rounded: {
    type: Boolean,
    default: true,
  },
});

const variantClasses = {
  info: 'bg-cyan-500/20 dark:bg-cyan-500/20 bg-cyan-100 text-cyan-400 dark:text-cyan-400 text-cyan-700',
  success: 'bg-emerald-500/20 dark:bg-emerald-500/20 bg-emerald-100 text-emerald-400 dark:text-emerald-400 text-emerald-700',
  warning: 'bg-amber-500/20 dark:bg-amber-500/20 bg-amber-100 text-amber-400 dark:text-amber-400 text-amber-700',
  danger: 'bg-red-500/20 dark:bg-red-500/20 bg-red-100 text-red-400 dark:text-red-400 text-red-700',
  default: 'bg-slate-500/20 dark:bg-slate-500/20 bg-slate-100 text-slate-400 dark:text-slate-400 text-slate-700',
};

const sizeClasses = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2 py-0.5 sm:px-2.5 sm:py-1 text-xs',
  lg: 'px-2.5 py-1 sm:px-3 sm:py-1.5 text-xs sm:text-sm',
};

const badgeClasses = computed(() => {
  const base = 'inline-flex items-center font-medium';
  const roundedClass = props.rounded ? 'rounded-full' : 'rounded';
  
  return [
    base,
    variantClasses[props.variant],
    sizeClasses[props.size],
    roundedClass,
  ].join(' ');
});
</script>

