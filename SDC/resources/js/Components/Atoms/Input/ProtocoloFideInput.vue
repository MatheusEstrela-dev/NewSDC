<template>
  <div>
    <input
      type="text"
      :value="modelValue"
      :class="[
        'atom-input atom-input-md w-full',
        modelValue ? 'atom-input-filled' : 'atom-input-normal',
      ]"
      placeholder="MG-F-XXXXXXX-XXXXX-XXXXXXXX"
      maxlength="25"
      @input="handleInput"
    />
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Formato esperado: MG-F-XXXXXXX-XXXXX-XXXXXXXX
    </p>
  </div>
</template>

<script setup>
const props = defineProps({
  modelValue: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue']);

function handleInput(event) {
  const digits = event.target.value.replace(/\D/g, '').substring(0, 20);
  let formatted = 'MG-F-';
  if (digits.length > 0) formatted += digits.substring(0, 7);
  if (digits.length > 7) formatted += '-' + digits.substring(7, 12);
  if (digits.length > 12) formatted += '-' + digits.substring(12, 20);
  emit('update:modelValue', formatted);
}
</script>
