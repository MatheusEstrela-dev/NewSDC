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
    validator: (value) => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'violet', 'black', 'topaz', 'vibrant-warning', 'vibrant-danger'].includes(value),
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

// O tom -400 vale para o tema escuro; sobre o fundo branco do tema claro ele
// fica abaixo de 3:1 e o icone some. No claro o texto desce para -600 (o
// vibrant-danger ja passa de 3:1 e fica igual nos dois). O dark segue identico.
const variantClasses = {
  primary: 'text-blue-600 hover:text-blue-500 hover:bg-blue-500/10 dark:text-blue-400 dark:hover:text-blue-300',
  secondary: 'text-slate-500 hover:text-slate-700 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-slate-300 dark:hover:bg-slate-700/50',
  success: 'text-emerald-600 hover:text-emerald-500 hover:bg-emerald-500/10 dark:text-emerald-400 dark:hover:text-emerald-300',
  danger: 'text-red-600 hover:text-red-500 hover:bg-red-500/10 dark:text-red-400 dark:hover:text-red-300',
  warning: 'text-amber-600 hover:text-amber-500 hover:bg-amber-500/10 dark:text-amber-400 dark:hover:text-amber-300',
  info: 'text-sky-600 hover:text-sky-500 hover:bg-sky-500/10 dark:text-sky-400 dark:hover:text-sky-300',
  violet: 'text-violet-600 hover:text-violet-500 hover:bg-violet-500/10 dark:text-violet-400 dark:hover:text-violet-300',
  black: 'text-white bg-black hover:bg-neutral-800',
  topaz: 'text-yellow-600 hover:text-yellow-500 hover:bg-yellow-500/10 dark:text-yellow-500 dark:hover:text-yellow-400',
  'vibrant-warning': 'text-orange-600 hover:text-orange-500 hover:bg-[#ff800d]/10 dark:text-[#ff800d] dark:hover:text-[#ff9d47]',
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

