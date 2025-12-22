<template>
  <div :class="cardClasses">
    <slot />
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'info', 'success', 'warning', 'danger'].includes(value),
  },
  padding: {
    type: String,
    default: 'md',
    validator: (value) => ['none', 'sm', 'md', 'lg'].includes(value),
  },
  hover: {
    type: Boolean,
    default: false,
  },
});

const variantClasses = {
  default: 'bg-slate-800/80 border-slate-700/50',
  info: 'bg-cyan-500/10 border-cyan-500/30',
  success: 'bg-emerald-500/10 border-emerald-500/30',
  warning: 'bg-amber-500/10 border-amber-500/30',
  danger: 'bg-red-500/10 border-red-500/30',
};

const paddingClasses = {
  none: '',
  sm: 'p-3',
  md: 'p-4',
  lg: 'p-6',
};

const cardClasses = computed(() => {
  const base = 'rounded-xl border backdrop-blur-sm transition-all duration-200';
  const hoverClass = props.hover ? 'hover:border-slate-600/50 hover:shadow-lg' : '';
  
  return [
    base,
    variantClasses[props.variant],
    paddingClasses[props.padding],
    hoverClass,
  ].filter(Boolean).join(' ');
});
</script>

