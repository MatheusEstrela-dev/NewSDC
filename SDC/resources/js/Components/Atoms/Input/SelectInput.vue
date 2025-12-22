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
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-4 py-2.5 text-sm',
  lg: 'px-5 py-3 text-base',
};

const selectClasses = computed(() => {
  const base = 'w-full rounded-lg border transition-all duration-200 outline-none focus:ring-2 appearance-none cursor-pointer bg-no-repeat bg-right pr-10';
  const bgClass = 'bg-slate-900/50 border-slate-700 text-slate-200';
  const errorClass = props.error ? 'border-red-500 focus:ring-red-500/20' : 'focus:border-blue-500 focus:ring-blue-500/20';
  const disabledClass = props.disabled ? 'opacity-50 cursor-not-allowed' : '';
  
  // Background image para seta do select
  const arrowBg = "url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e\")";
  
  return [
    base,
    bgClass,
    errorClass,
    sizeClasses[props.size],
    disabledClass,
  ].filter(Boolean).join(' ') + `; background-image: ${arrowBg}; background-position: right 0.5rem center; background-size: 1.5em 1.5em;`;
});
</script>

