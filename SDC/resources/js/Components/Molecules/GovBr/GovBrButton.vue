<template>
  <button
    v-if="asButton"
    type="button"
    class="inline-flex items-center justify-center gap-2 rounded-lg border font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#ffcd07] focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
    :class="[sizeClass, variantClass, fullWidth ? 'w-full' : '']"
    :disabled="disabled"
    :title="title"
    @click="$emit('click', $event)"
  >
    <span class="uppercase tracking-normal">{{ label }}</span>
    <GovBrLogo size="sm" :inverted="variant === 'primary'" />
  </button>

  <a
    v-else
    :href="href"
    target="_blank"
    rel="noopener noreferrer"
    class="inline-flex items-center justify-center gap-2 rounded-lg border font-bold shadow-sm transition-all focus:outline-none focus:ring-2 focus:ring-[#ffcd07] focus:ring-offset-2"
    :class="[sizeClass, variantClass, fullWidth ? 'w-full' : '']"
    :title="title"
  >
    <span class="uppercase tracking-normal">{{ label }}</span>
    <GovBrLogo size="sm" :inverted="variant === 'primary'" />
  </a>
</template>

<script setup>
import { computed } from 'vue';
import GovBrLogo from '@/Components/Atoms/GovBr/GovBrLogo.vue';

defineEmits(['click']);

const props = defineProps({
  href: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: 'Entrar com',
  },
  size: {
    type: String,
    default: 'md',
    validator: value => ['sm', 'md'].includes(value),
  },
  variant: {
    type: String,
    default: 'primary',
    validator: value => ['primary', 'light'].includes(value),
  },
  fullWidth: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Abrir GOV.br',
  },
});

const asButton = computed(() => !props.href);

const sizeClass = computed(() => ({
  sm: 'min-h-9 px-3 py-1.5 text-xs',
  md: 'min-h-11 px-4 py-2 text-sm',
}[props.size]));

const variantClass = computed(() => ({
  primary: 'border-[#1351b4] bg-[#1351b4] text-white hover:border-[#0c326f] hover:bg-[#0c326f] hover:text-white',
  light: 'border-white bg-white text-[#1351b4] hover:border-slate-100 hover:bg-slate-50 hover:text-[#0c326f]',
}[props.variant]));
</script>
