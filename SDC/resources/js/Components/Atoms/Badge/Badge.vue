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
  info: 'bg-cyan-500/20 text-cyan-400',
  success: 'bg-emerald-500/20 text-emerald-400',
  warning: 'bg-amber-500/20 text-amber-400',
  danger: 'bg-red-500/20 text-red-400',
  default: 'bg-slate-500/20 text-slate-400',
};

const sizeClasses = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2.5 py-1 text-xs',
  lg: 'px-3 py-1.5 text-sm',
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

