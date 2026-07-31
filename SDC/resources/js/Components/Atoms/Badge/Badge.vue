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
    validator: (value) => ['info', 'success', 'warning', 'danger', 'default', 'neutral'].includes(value),
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
  info: 'bg-cyan-100 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-400',
  success: 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400',
  warning: 'bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400',
  danger: 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
  default: 'bg-slate-100 dark:bg-slate-500/20 text-slate-700 dark:text-slate-400',
  // Cinza mais forte que o default, para estados encerrados sem desfecho
  // (arquivado) nao se confundirem com estados apenas neutros (rascunho).
  neutral: 'bg-slate-200 dark:bg-slate-600/30 text-slate-800 dark:text-slate-300',
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

