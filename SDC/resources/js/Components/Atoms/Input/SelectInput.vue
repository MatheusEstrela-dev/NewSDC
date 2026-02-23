<template>
  <select
    :value="modelValue"
    :disabled="disabled"
    :required="required"
    :class="selectClasses"
    @change="$emit('update:modelValue', $event.target.value)"
    @blur="$emit('blur', $event)"
    @focus="$emit('focus', $event)"
  >
    <option v-if="placeholder" value="">{{ placeholder }}</option>
    <option
      v-for="option in options"
      :key="getOptionValue(option)"
      :value="getOptionValue(option)"
    >
      {{ getOptionLabel(option) }}
    </option>
  </select>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  options: {
    type: Array,
    required: true,
  },
  placeholder: {
    type: String,
    default: 'Selecione...',
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
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

function getOptionValue(option) {
  if (typeof option === 'string' || typeof option === 'number') {
    return option;
  }
  return option.value ?? option.id ?? option;
}

function getOptionLabel(option) {
  if (typeof option === 'string' || typeof option === 'number') {
    return option;
  }
  return option.label ?? option.name ?? option.text ?? option.value ?? option;
}

const sizeClasses = {
  sm: 'atom-input-sm',
  md: 'atom-input-md',
  lg: 'atom-input-lg',
};

const isFilled = computed(() => {
  if (props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const stateClass = computed(() => {
  if (props.error) return 'atom-input-error';
  if (isFilled.value) return 'atom-input-filled';
  return 'atom-input-normal';
});

const selectClasses = computed(() => {
  return [
    'atom-input',
    'atom-select',
    // Fallback classes quando .atom-input CSS não carrega
    'rounded-lg',
    'bg-slate-900/50',
    'border',
    'border-slate-700',
    'text-slate-200',
    'w-full',
    'px-4',
    'py-2',
    stateClass.value,
    sizeClasses[props.size],
    props.disabled ? 'atom-input-disabled' : '',
  ].filter(Boolean).join(' ');
});
</script>

