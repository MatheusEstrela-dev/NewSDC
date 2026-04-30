<template>
  <div class="relative flex items-center">
    <span class="absolute left-3 text-sm text-slate-400 dark:text-slate-500 select-none">R$</span>
    <input
      type="text"
      :value="modelValue"
      :disabled="disabled"
      :placeholder="placeholder"
      :class="[
        'atom-input atom-input-md w-full pl-9',
        disabled ? 'atom-input-disabled' : (modelValue ? 'atom-input-filled' : 'atom-input-normal'),
      ]"
      @keyup="handleKeyup"
      @blur="$emit('blur', $event)"
    />
  </div>
</template>

<script setup>
import { formatCurrency } from '@/composables/ui/useDesastreMask';

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
});

const emit = defineEmits(['update:modelValue', 'blur']);

function handleKeyup(event) {
  const raw = event.target.value.replace(/\D/g, '');
  const formatted = raw ? formatCurrency(parseFloat(raw) / 100) : '';
  emit('update:modelValue', formatted);
}
</script>
