<template>
  <div class="form-field">
    <Label v-if="label" :for-id="inputId" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <DatePicker
      :id="inputId"
      :model-value="modelValue"
      :type="pickerType"
      :placeholder="placeholder"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      :error="!!error"
      :show-icon="showIcon"
      @update:model-value="$emit('update:modelValue', $event)"
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
import DatePicker from '../../Form/DatePicker.vue';

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
    validator: (value) => ['date', 'datetime-local', 'datetime'].includes(value),
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

// Normaliza o tipo para o contrato do DatePicker (date | datetime)
const pickerType = computed(() => (props.type === 'date' ? 'date' : 'datetime'));
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
