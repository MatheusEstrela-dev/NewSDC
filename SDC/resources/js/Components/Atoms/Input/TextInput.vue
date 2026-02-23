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
  sm: 'atom-input-sm',
  md: 'atom-input-md',
  lg: 'atom-input-lg',
};

const isFilled = computed(() => {
  if (props.readonly || props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const stateClass = computed(() => {
  if (props.readonly) return 'atom-input-readonly';
  if (props.error) return 'atom-input-error';
  if (isFilled.value) return 'atom-input-filled';
  return 'atom-input-normal';
});

const inputClasses = computed(() => {
  return [
    'atom-input',
    // Fallback classes quando .atom-input CSS não carrega
    'rounded-lg',
    'bg-slate-900/50',
    'border',
    'border-slate-700',
    'text-slate-200',
    'placeholder:text-slate-500',
    'w-full',
    'px-4',
    'py-2',
    stateClass.value,
    sizeClasses[props.size],
    props.disabled ? 'atom-input-disabled' : '',
  ].filter(Boolean).join(' ');
});
</script>

