<template>
  <div class="form-field">
    <label v-if="label" :for="fieldId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-400 ml-1">*</span>
    </label>

    <div class="relative">
      <select
        :id="fieldId"
        :value="modelValue"
        @change="handleChange"
        :disabled="disabled"
        :required="required"
        :class="[
          'form-select',
          error ? 'form-select-error' : isFilled ? 'form-select-filled' : 'form-select-normal',
          disabled ? 'form-select-disabled' : '',
        ]"
      >
        <option value="" v-if="placeholder">{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>

      <!-- Ícone de dropdown -->
      <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
      </div>
    </div>

    <!-- Mensagem de erro -->
    <p v-if="error" class="form-error">{{ error }}</p>

    <!-- Mensagem de ajuda -->
    <p v-if="hint && !error" class="form-hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

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
    default: () => [],
  },
  placeholder: {
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
});

const emit = defineEmits(['update:modelValue']);

const fieldId = computed(() => `select-${Math.random().toString(36).substr(2, 9)}`);

// Computed para verificar se o select está preenchido
const isFilled = computed(() => {
  if (props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const handleChange = (event) => {
  const value = event.target.value;
  emit('update:modelValue', value);
};
</script>

<style scoped>
.form-field {
  @apply w-full;
}

.form-label {
  @apply block text-sm font-medium text-slate-300 mb-2;
}

.form-select {
  @apply w-full px-4 py-2.5 pr-10 rounded-lg bg-slate-900/50 text-slate-200
    transition-all duration-200 appearance-none cursor-pointer
    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950;
}

.form-select-normal {
  @apply border border-slate-700/50
    hover:border-slate-600
    focus:border-blue-500 focus:ring-blue-500/20;
}

.form-select-filled {
  @apply border-2 border-emerald-500/60
    hover:border-emerald-500/80
    focus:border-emerald-500 focus:ring-emerald-500/20
    shadow-sm shadow-emerald-500/10;
}

.form-select-error {
  @apply border border-red-500/50
    focus:border-red-500 focus:ring-red-500/20;
}

.form-select-disabled {
  @apply bg-slate-900/20 cursor-not-allowed text-slate-500 opacity-60;
}

.form-error {
  @apply mt-1.5 text-xs text-red-400 flex items-center gap-1;
}

.form-hint {
  @apply mt-1.5 text-xs text-slate-500;
}

/* Estilo para opções no dropdown */
.form-select option {
  @apply bg-slate-900 text-slate-200;
}
</style>
