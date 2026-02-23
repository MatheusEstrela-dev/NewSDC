<template>
  <div class="form-field">
    <Label v-if="label" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <div :class="['flex gap-4 mt-2', inline ? 'flex-row flex-wrap' : 'flex-col']">
      <RadioInput
        v-for="option in options"
        :key="getOptionValue(option)"
        :model-value="modelValue"
        :value="getOptionValue(option)"
        :name="name"
        :label="getOptionLabel(option)"
        :disabled="disabled"
        :required="required"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>
    <p v-if="error" class="mt-1 text-xs text-red-400">
      {{ error }}
    </p>
    <p v-else-if="hint" class="mt-1 text-xs text-slate-500">
      {{ hint }}
    </p>
  </div>
</template>

<script setup>
import Label from '../../Atoms/Typography/Label.vue';
import RadioInput from '../../Atoms/Input/RadioInput.vue';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: null,
  },
  options: {
    type: Array,
    required: true,
  },
  name: {
    type: String,
    required: true,
  },
  label: {
    type: String,
    default: '',
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
  hint: {
    type: String,
    default: '',
  },
  inline: {
    type: Boolean,
    default: true,
  },
  labelSize: {
    type: String,
    default: 'md',
  },
});

defineEmits(['update:modelValue']);

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
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
