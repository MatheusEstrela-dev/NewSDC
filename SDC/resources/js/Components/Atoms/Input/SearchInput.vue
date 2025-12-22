<template>
  <div class="relative">
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :class="inputClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
      <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: 'Buscar...',
  },
  disabled: {
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
  size: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm pl-10',
  md: 'px-4 py-2.5 text-sm pl-10',
  lg: 'px-5 py-3 text-base pl-12',
};

const inputClasses = computed(() => {
  const base = 'w-full rounded-lg border transition-all duration-200 outline-none focus:ring-2';
  const bgClass = 'bg-slate-900/50 border-slate-700 text-slate-200 placeholder-slate-500';
  const errorClass = props.error ? 'border-red-500 focus:ring-red-500/20' : 'focus:border-blue-500 focus:ring-blue-500/20';
  const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';
  
  return [
    base,
    bgClass,
    errorClass,
    sizeClasses[props.size],
    disabledClass,
  ].filter(Boolean).join(' ');
});
</script>

