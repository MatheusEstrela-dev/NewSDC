<template>
  <div class="form-field">
    <label v-if="label" :for="fieldId" class="form-label">
      {{ label }}
      <span v-if="required" class="text-red-400 ml-1">*</span>
    </label>

    <div class="relative">
      <!-- Textarea -->
      <textarea
        v-if="type === 'textarea'"
        :id="fieldId"
        :value="modelValue"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :rows="rows"
        :class="[
          'form-input',
          error ? 'form-input-error' : isFilled ? 'form-input-filled' : 'form-input-normal',
          readonly ? 'form-input-readonly' : '',
          disabled ? 'form-input-disabled' : '',
        ]"
      ></textarea>

      <!-- Input -->
      <input
        v-else
        :id="fieldId"
        :type="type"
        :value="modelValue"
        @input="handleInput"
        @blur="handleBlur"
        :placeholder="placeholder"
        :readonly="readonly"
        :disabled="disabled"
        :required="required"
        :class="[
          'form-input',
          error ? 'form-input-error' : isFilled ? 'form-input-filled' : 'form-input-normal',
          readonly ? 'form-input-readonly' : '',
          disabled ? 'form-input-disabled' : '',
        ]"
      />

      <!-- Slot para ícone ou elemento à direita -->
      <div v-if="$slots.suffix && type !== 'textarea'" class="absolute inset-y-0 right-0 flex items-center pr-3">
        <slot name="suffix"></slot>
      </div>
    </div>

    <!-- Mensagem de erro -->
    <p v-if="error" class="form-error">{{ error }}</p>

    <!-- Mensagem de ajuda -->
    <p v-if="hint && !error" class="form-hint">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: '',
  },
  label: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  placeholder: {
    type: String,
    default: '',
  },
  readonly: {
    type: Boolean,
    default: false,
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
  mask: {
    type: String,
    default: '',
  },
  rows: {
    type: Number,
    default: 3,
  },
});

const emit = defineEmits(['update:modelValue', 'blur']);

// ID estável gerado uma única vez na inicialização do componente
const fieldId = ref(`field-${Math.random().toString(36).substring(2, 11)}`);

// Computed para verificar se o campo está preenchido
const isFilled = computed(() => {
  if (props.readonly || props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const handleInput = (event) => {
  let value = event.target.value;

  // Aplica máscara se fornecida
  if (props.mask) {
    value = applyMask(value, props.mask);
  }

  emit('update:modelValue', value);
};

const handleBlur = (event) => {
  emit('blur', event);
};

/**
 * Aplica máscara ao valor
 * Suporta: #####-### para CEP, (##) #####-#### para telefone, etc.
 */
const applyMask = (value, mask) => {
  const cleanValue = value.replace(/\D/g, '');
  let maskedValue = '';
  let valueIndex = 0;

  for (let i = 0; i < mask.length && valueIndex < cleanValue.length; i++) {
    if (mask[i] === '#') {
      maskedValue += cleanValue[valueIndex];
      valueIndex++;
    } else {
      maskedValue += mask[i];
    }
  }

  return maskedValue;
};
</script>

<style scoped>
.form-field {
  @apply w-full;
}

.form-label {
  @apply block text-sm font-medium text-slate-300 mb-2;
}

.form-input {
  @apply w-full px-4 py-2.5 rounded-lg bg-slate-900/50 text-slate-200
    placeholder-slate-500 transition-all duration-200
    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-950;
}

.form-input-normal {
  @apply border border-slate-700/50
    hover:border-slate-600
    focus:border-blue-500 focus:ring-blue-500/20;
}

.form-input-filled {
  @apply border-2 border-emerald-500/60
    hover:border-emerald-500/80
    focus:border-emerald-500 focus:ring-emerald-500/20
    shadow-sm shadow-emerald-500/10;
}

.form-input-error {
  @apply border border-red-500/50
    focus:border-red-500 focus:ring-red-500/20;
}

.form-input-readonly {
  @apply bg-slate-900/30 cursor-not-allowed text-slate-400;
}

.form-input-disabled {
  @apply bg-slate-900/20 cursor-not-allowed text-slate-500 opacity-60;
}

.form-error {
  @apply mt-1.5 text-xs text-red-400 flex items-center gap-1;
}

.form-hint {
  @apply mt-1.5 text-xs text-slate-500;
}
</style>
