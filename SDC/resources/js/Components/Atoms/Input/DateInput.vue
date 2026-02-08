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
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus']);

const formattedValue = computed(() => {
  if (!props.modelValue) return '';

  if (props.modelValue.match(/^\d{4}-\d{2}-\d{2}/)) {
    return props.modelValue;
  }

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
    'w-full',
    'px-4',
    'py-2',
    stateClass.value,
    sizeClasses[props.size],
    props.disabled ? 'atom-input-disabled' : '',
    props.showIcon ? 'pr-10' : '',
  ].filter(Boolean).join(' ');
});
</script>

