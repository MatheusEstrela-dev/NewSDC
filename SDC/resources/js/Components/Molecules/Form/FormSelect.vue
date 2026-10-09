<template>
  <div class="form-field">
    <Label v-if="label" :for-id="selectId" :required="required" :size="labelSize">
      {{ label }}
    </Label>
    <SelectInput
      :id="selectId"
      :model-value="modelValue"
      :options="options"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      :error="!!error"
      :size="size"
      :aria-label="ariaLabel"
      @update:model-value="$emit('update:modelValue', $event)"
      @blur="$emit('blur', $event)"
      @focus="$emit('focus', $event)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">
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
import SelectInput from '../../Atoms/Input/SelectInput.vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
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
    type: String,
    default: '',
  },
  hint: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
  },
  labelSize: {
    type: String,
    default: 'md',
  },
  // Id explicito do select. Sem ele o id sai do label, e o mesmo campo
  // repetido na tela (um bloco por pessoa, por exemplo) gerava ids iguais:
  // o label de um bloco focava o campo do primeiro.
  id: {
    type: String,
    default: '',
  },
  // Rotulo acessivel para campo sem Label visivel (celula de tabela).
  ariaLabel: {
    type: String,
    default: undefined,
  },
});

defineEmits(['update:modelValue', 'blur', 'focus']);

const selectId = computed(() => {
  if (props.id) return props.id;
  return props.label ? `select-${props.label.toLowerCase().replace(/\s+/g, '-')}` : `select-${Math.random().toString(36).substr(2, 9)}`;
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>

