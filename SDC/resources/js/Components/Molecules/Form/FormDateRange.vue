<template>
  <div class="form-field">
    <Label v-if="label" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <Label v-if="startLabel" :for-id="startId" :size="'sm'" color="muted">
          {{ startLabel }}
        </Label>
        <DateInput
          :id="startId"
          :model-value="startValue"
          :placeholder="startPlaceholder"
          :disabled="disabled"
          :required="required"
          :error="!!error"
          @update:model-value="handleStartChange"
        />
      </div>
      <div>
        <Label v-if="endLabel" :for-id="endId" :size="'sm'" color="muted">
          {{ endLabel }}
        </Label>
        <DateInput
          :id="endId"
          :model-value="endValue"
          :placeholder="endPlaceholder"
          :disabled="disabled"
          :error="!!error"
          @update:model-value="handleEndChange"
        />
      </div>
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-400">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Label from '../../Atoms/Typography/Label.vue';
import DateInput from '../../Atoms/Input/DateInput.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ start: '', end: '' }),
  },
  label: {
    type: String,
    default: '',
  },
  startLabel: {
    type: String,
    default: 'Data Início',
  },
  endLabel: {
    type: String,
    default: 'Data Fim',
  },
  startPlaceholder: {
    type: String,
    default: 'dd/mm/aaaa',
  },
  endPlaceholder: {
    type: String,
    default: 'dd/mm/aaaa',
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
    type: String,
    default: '',
  },
  labelSize: {
    type: String,
    default: 'md',
  },
});

const emit = defineEmits(['update:modelValue']);

const startValue = computed(() => props.modelValue?.start || '');
const endValue = computed(() => props.modelValue?.end || '');

// IDs estáveis para evitar re-render/realinhamento desnecessário
const uid = Math.random().toString(36).slice(2, 9);
const startId = `date-start-${uid}`;
const endId = `date-end-${uid}`;

function handleStartChange(value) {
  emit('update:modelValue', {
    ...props.modelValue,
    start: value,
  });
}

function handleEndChange(value) {
  emit('update:modelValue', {
    ...props.modelValue,
    end: value,
  });
}
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>

