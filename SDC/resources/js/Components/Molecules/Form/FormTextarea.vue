<template>
  <div class="form-field">
    <Label v-if="label" :for-id="inputId" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <textarea
      :id="inputId"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :rows="rows"
      :class="textareaClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-400">
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-1 text-xs text-slate-500">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Label from '../../Atoms/Typography/Label.vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  placeholder: {
    type: String,
    default: '',
  },
  rows: {
    type: [Number, String],
    default: 4,
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
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  labelSize: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const inputId = computed(() => {
  return props.label
    ? `textarea-${props.label.toLowerCase().replace(/\s+/g, '-')}`
    : `textarea-${Math.random().toString(36).substr(2, 9)}`;
});

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

const textareaClasses = computed(() => {
  return [
    'atom-input',
    'atom-input-md',
    'resize-y',
    stateClass.value,
    props.disabled ? 'atom-input-disabled' : '',
  ].filter(Boolean).join(' ');
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
