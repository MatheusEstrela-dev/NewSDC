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
        :inputmode="numericInputMode"
        :maxlength="maskedMaxLength"
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

const MASK_PATTERNS = {
  phone: '(##) #####-####',
  cpf:   '###.###.###-##',
  cep:   '#####-###',
};

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

const resolvedMask = computed(() => MASK_PATTERNS[props.mask] ?? props.mask);
const numericInputMode = computed(() => (props.mask && props.mask in MASK_PATTERNS ? 'numeric' : undefined));
const maskedMaxLength = computed(() => {
  if (!resolvedMask.value) return undefined;
  return resolvedMask.value.length || undefined;
});

// Computed para verificar se o campo está preenchido
const isFilled = computed(() => {
  if (props.readonly || props.disabled) return false;
  const value = props.modelValue;
  return value !== null && value !== undefined && value !== '';
});

const handleInput = (event) => {
  let value = event.target.value;

  if (props.mask) {
    value = applyMask(value, props.mask);
    event.target.value = value;
  }

  emit('update:modelValue', value);
};

const handleBlur = (event) => {
  emit('blur', event);
};

const applyMask = (value, mask) => {
  const pattern = MASK_PATTERNS[mask] ?? mask;
  if (!pattern) return value;
  const cleanValue = value.replace(/\D/g, '');
  let maskedValue = '';
  let valueIndex = 0;

  for (let i = 0; i < pattern.length && valueIndex < cleanValue.length; i++) {
    if (pattern[i] === '#') {
      maskedValue += cleanValue[valueIndex];
      valueIndex++;
    } else {
      maskedValue += pattern[i];
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
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}

.form-input {
  @apply w-full px-3 py-2 sm:px-4 sm:py-2.5 text-sm sm:text-base rounded-lg bg-slate-50 dark:bg-slate-900/50 text-slate-900 dark:text-slate-200
    placeholder-slate-400 dark:placeholder-slate-500 transition-all duration-200
    focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-950;
}

.form-input-normal {
  @apply border border-slate-300 dark:border-slate-700/50
    hover:border-slate-400 dark:hover:border-slate-600
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
  @apply bg-slate-100 dark:bg-slate-900/30 cursor-not-allowed text-slate-500 dark:text-slate-400;
}

.form-input-disabled {
  @apply bg-slate-100 dark:bg-slate-900/20 cursor-not-allowed text-slate-400 dark:text-slate-500 opacity-60;
}

.form-error {
  @apply mt-1 sm:mt-1.5 text-xs text-red-400 flex items-center gap-1;
}

.form-hint {
  @apply mt-1 sm:mt-1.5 text-xs text-slate-500;
}

/* Mobile touch-friendly sizing */
@media (max-width: 640px) {
  .form-input {
    min-height: 2.75rem;
    font-size: 16px; /* Previne zoom no iOS */
  }
}
</style>
