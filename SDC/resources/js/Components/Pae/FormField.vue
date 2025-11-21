<template>
  <div>
    <label class="block text-sm font-medium text-slate-300 mb-2">{{ label }}</label>
    <div class="relative">
      <input
        :type="type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :readonly="readonly"
        :class="[
          readonly ? 'form-input-readonly' : 'form-input',
          icon ? 'pl-10' : '',
          inputClass,
        ]"
        :placeholder="placeholder"
      />
      <div v-if="icon" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <component :is="iconComponent" class="h-4 w-4 text-slate-400" />
      </div>
    </div>
  </div>
</template>

<script setup>
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';

defineProps({
  label: {
    type: String,
    required: true,
  },
  modelValue: {
    type: [String, Number],
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  placeholder: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: null,
  },
  inputClass: {
    type: String,
    default: '',
  },
});

defineEmits(['update:modelValue']);

const iconComponent = DocumentTextIcon; // Simplificado, pode ser expandido
</script>

