<template>
  <label v-if="campo.tipo === 'checkbox'" class="flex items-center gap-2 rounded-lg border border-slate-200 p-2 text-sm dark:border-slate-700">
    <input type="checkbox" :checked="!!modelValue" :disabled="disabled" class="rounded" @change="$emit('update:modelValue', $event.target.checked)" />
    <span class="text-slate-700 dark:text-slate-200">{{ campo.label }}</span>
  </label>
  <FormField v-else :label="campo.label" :model-value="modelValue ?? ''" :disabled="disabled" required maxlength="500" :error="erro ?? ''"
    @update:model-value="$emit('update:modelValue', $event)" @blur="$emit('blur')" />
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';

defineProps({
  campo: { type: Object, required: true },
  modelValue: { type: [String, Boolean, Number], default: null },
  disabled: { type: Boolean, default: false },
  erro: { type: String, default: null },
});
defineEmits(['update:modelValue', 'blur']);
</script>
