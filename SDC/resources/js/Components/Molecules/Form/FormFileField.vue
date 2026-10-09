<template>
  <div class="form-field">
    <Label v-if="label" :for-id="inputId" :required="required">{{ label }}</Label>
    <input
      :id="inputId"
      ref="entrada"
      type="file"
      :accept="accept"
      :disabled="disabled"
      :required="required"
      class="block w-full min-w-0 text-sm text-slate-700 file:mr-3 file:mb-1 sm:file:mb-0 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-blue-500 disabled:opacity-60 dark:text-slate-200"
      @change="$emit('update:modelValue', $event.target.files?.[0] ?? null)"
    />
    <p v-if="error" class="mt-1 text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    <p v-else-if="hint" class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import Label from '@/Components/Atoms/Typography/Label.vue';

const props = defineProps({
  modelValue: { type: Object, default: null },
  label: { type: String, default: '' },
  accept: { type: String, default: 'application/pdf,.pdf' },
  error: { type: String, default: '' },
  hint: { type: String, default: '' },
  required: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  id: { type: String, default: '' },
});

defineEmits(['update:modelValue']);

const entrada = ref(null);
const inputId = computed(() => props.id || `file-${Math.random().toString(36).slice(2, 11)}`);

// O pai zera o modelo depois do envio; o input nativo precisa acompanhar.
watch(() => props.modelValue, (valor) => {
  if (valor === null && entrada.value) entrada.value.value = '';
});
</script>

<style scoped>
.form-field {
  @apply w-full;
}
</style>
