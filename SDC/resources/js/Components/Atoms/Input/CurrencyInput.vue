<template>
  <div class="relative flex items-center">
    <span class="absolute left-3 text-sm text-slate-400 dark:text-slate-500 select-none">R$</span>
    <input
      type="text"
      :value="modelValue"
      :disabled="disabled"
      :placeholder="placeholder"
      :class="[
        'atom-input w-full',
        size === 'sm' ? 'atom-input-sm' : (size === 'lg' ? 'atom-input-lg' : 'atom-input-md'),
        disabled ? 'atom-input-disabled' : (modelValue ? 'atom-input-filled' : 'atom-input-normal'),
      ]"
      style="padding-left: 3rem !important;"
      @keyup="handleKeyup"
      @blur="$emit('blur', $event)"
    />
  </div>
</template>

<script setup>
import { formatCurrency } from '@/Composables/ui/useDesastreMask';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: '0,00',
  },
  size: {
    type: String,
    default: 'md',
  },
});

const emit = defineEmits(['update:modelValue', 'blur']);

function handleKeyup(event) {
  const raw = event.target.value.replace(/\D/g, '');
  const formatted = raw ? formatCurrency(parseFloat(raw) / 100) : '';
  emit('update:modelValue', formatted);
}
</script>
