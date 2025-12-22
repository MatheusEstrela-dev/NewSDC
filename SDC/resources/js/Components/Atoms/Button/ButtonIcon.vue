<template>
  <button
    :type="type"
    :disabled="disabled"
    :class="buttonClasses"
    :title="title"
    @click="$emit('click', $event)"
  >
    <component :is="icon" :class="iconSizeClasses" />
  </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  icon: {
    type: [Object, Function],
    required: true,
  },
  variant: {
    type: String,
    default: 'secondary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  type: {
    type: String,
    default: 'button',
  },
  title: {
    type: String,
    default: '',
  },
});

defineEmits(['click']);

const variantClasses = {
  primary: 'text-blue-400 hover:text-blue-300 hover:bg-blue-500/10',
  secondary: 'text-slate-400 hover:text-slate-300 hover:bg-slate-700/50',
  success: 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10',
  danger: 'text-red-400 hover:text-red-300 hover:bg-red-500/10',
  warning: 'text-amber-400 hover:text-amber-300 hover:bg-amber-500/10',
  info: 'text-cyan-400 hover:text-cyan-300 hover:bg-cyan-500/10',
};

const sizeClasses = {
  sm: 'p-1',
  md: 'p-2',
  lg: 'p-3',
};

const iconSizeClasses = {
  sm: 'w-4 h-4',
  md: 'w-5 h-5',
  lg: 'w-6 h-6',
};

const buttonClasses = computed(() => {
  const base = 'inline-flex items-center justify-center rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';
  
  return [
    base,
    variantClasses[props.variant],
    sizeClasses[props.size],
    props.disabled ? 'cursor-not-allowed' : 'cursor-pointer',
  ].join(' ');
});
</script>

