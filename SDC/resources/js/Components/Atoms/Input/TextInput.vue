<template>
  <input
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    :class="inputClasses"
    @input="$emit('update:modelValue', $event.target.value)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
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
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

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
  
  return [
    base,
    bgClass,
    errorClass,
    sizeClasses[props.size],
    disabledClass,
  ].filter(Boolean).join(' ');
});
</script>

