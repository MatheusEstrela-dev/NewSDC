<template>
  <component
    :is="componentTag"
    :href="href"
    :type="href ? undefined : type"
    :disabled="disabled"
    :class="buttonClasses"
    :title="title"
    @click="handleClick"
  >
    <component :is="icon" :class="iconClasses" />
  </component>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
  icon: {
    type: [Object, Function],
    required: true,
  },
  variant: {
    type: String,
    default: 'secondary',
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'black', 'topaz', 'vibrant-warning', 'vibrant-danger'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  touchTarget: {
    type: Boolean,
    default: true,
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
  href: {
    type: String,
    default: null,
  },
  confirmMessage: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['click']);

const componentTag = computed(() => props.href ? Link : 'button');

const handleClick = (event) => {
  if (props.confirmMessage) {
    if (!confirm(props.confirmMessage)) {
      event.preventDefault();
      return;
    }
  }
  emit('click', event);
};

const variantClasses = {
  primary: 'text-blue-400 hover:text-blue-300 hover:bg-blue-500/10',
  secondary: 'text-slate-400 hover:text-slate-300 hover:bg-slate-700/50',
  success: 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-500/10',
  danger: 'text-red-400 hover:text-red-300 hover:bg-red-500/10',
  warning: 'text-amber-400 hover:text-amber-300 hover:bg-amber-500/10',
  info: 'text-sky-400 hover:text-sky-300 hover:bg-sky-500/10',
  black: 'text-white bg-black hover:bg-neutral-800',
  topaz: 'text-yellow-500 hover:text-yellow-400 hover:bg-yellow-500/10',
  'vibrant-warning': 'text-[#ff800d] hover:text-[#ff9d47] hover:bg-[#ff800d]/10',
  'vibrant-danger': 'text-[#ff4d00] hover:text-[#ff6a26] hover:bg-[#ff4d00]/10',
};

const SIZE_CONFIG = {
  sm: {
    padding: 'p-1',
    paddingTouch: 'p-1.5',
    minSize: 'min-w-[28px] min-h-[28px]',
    icon: 'w-4 h-4',
  },
  md: {
    padding: 'p-1.5',
    paddingTouch: 'p-2',
    minSize: 'min-w-[32px] min-h-[32px]',
    icon: 'w-5 h-5',
  },
  lg: {
    padding: 'p-2',
    paddingTouch: 'p-2.5',
    minSize: 'min-w-[40px] min-h-[40px]',
    icon: 'w-6 h-6',
  },
};

const buttonClasses = computed(() => {
  const config = SIZE_CONFIG[props.size];
  const base = 'inline-flex items-center justify-center rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation';

  return [
    base,
    variantClasses[props.variant],
    props.touchTarget ? config.paddingTouch : config.padding,
    props.touchTarget ? config.minSize : '',
    props.disabled ? 'cursor-not-allowed' : 'cursor-pointer',
  ].filter(Boolean).join(' ');
});

const iconClasses = computed(() => SIZE_CONFIG[props.size].icon);
</script>

