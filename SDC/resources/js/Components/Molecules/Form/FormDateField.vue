<template>
  <div class="form-field">
    <Label v-if="label" :for-id="inputId" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <DateInput
      :id="inputId"
      :model-value="modelValue"
      :type="type"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :error="!!error"
      :show-icon="showIcon"
      @update:model-value="$emit('update:modelValue', $event)"
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
import DateInput from '../../Atoms/Input/DateInput.vue';

const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
  label: {
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
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  labelSize: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const inputId = computed(() => {
  return props.label
    ? `date-field-${props.label.toLowerCase().replace(/\s+/g, '-')}`
    : `date-field-${Math.random().toString(36).substr(2, 9)}`;
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
