<template>
  <button
    type="button"
    :class="[
      'w-11 h-6 flex items-center rounded-full transition-all duration-200',
      'focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white',
      'disabled:opacity-60 disabled:cursor-not-allowed',
      'dark:focus:ring-offset-slate-900 focus:ring-blue-500',
      modelValue ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700',
    ]"
    :aria-checked="modelValue"
    :aria-disabled="disabled"
    :disabled="disabled"
    role="switch"
    @click="alternar"
  >
    <span
      :class="[
        'w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200',
        modelValue ? 'translate-x-5' : 'translate-x-0.5',
      ]"
    />
  </button>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

const alternar = () => {
  if (!props.disabled) emit('update:modelValue', !props.modelValue);
};
</script>
