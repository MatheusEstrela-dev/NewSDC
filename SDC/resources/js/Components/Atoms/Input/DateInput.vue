<template>
  <div class="relative">
    <input
      :type="type"
      :value="formattedValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :class="inputClasses"
      @input="handleInput"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <div v-if="showIcon" class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
      <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import TextInput from './TextInput.vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'date',
    validator: (value) => ['date', 'datetime-local'].includes(value),
  },
  placeholder: {
    type: String,
    default: 'dd/mm/aaaa',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const formattedValue = computed(() => {
  if (!props.modelValue) return '';
  
  // Se já está no formato correto (YYYY-MM-DD), retorna
  if (props.modelValue.match(/^\d{4}-\d{2}-\d{2}/)) {
    return props.modelValue;
  }
  
  // Tenta converter de formato brasileiro para ISO
  const parts = props.modelValue.split('/');
  if (parts.length === 3) {
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
  }
  
  return props.modelValue;
});

function handleInput(event) {
  emit('update:modelValue', event.target.value);
}

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2.5 text-sm',
  lg: 'px-5 py-3 text-base',
};

const inputClasses = computed(() => {
  const base = 'w-full rounded-lg border transition-all duration-200 outline-none focus:ring-2';
  const bgClass = props.readonly 
    ? 'bg-slate-800/50 border-slate-700/30 text-slate-400 cursor-default'
    : 'bg-slate-900/50 border-slate-700 text-slate-200 placeholder-slate-500';
  const errorClass = props.error ? 'border-red-500 focus:ring-red-500/20' : 'focus:border-blue-500 focus:ring-blue-500/20';
  const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';
  const iconPadding = props.showIcon ? 'pr-10' : '';
  
  return [
    base,
    bgClass,
    errorClass,
    sizeClasses.md,
    disabledClass,
    iconPadding,
  ].filter(Boolean).join(' ');
});
</script>

