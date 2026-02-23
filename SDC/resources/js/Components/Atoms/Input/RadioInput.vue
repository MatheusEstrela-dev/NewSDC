<template>
  <label
    :class="[
      'flex items-center gap-2 cursor-pointer group',
      disabled ? 'opacity-50 cursor-not-allowed' : ''
    ]"
  >
    <input
      type="radio"
      :name="name"
      :value="value"
      :checked="modelValue === value"
      :disabled="disabled"
      :required="required"
      class="w-4 h-4 text-blue-600 bg-slate-900/50 border-slate-600 focus:ring-blue-500 focus:ring-2 focus:ring-offset-0 dark:focus:ring-offset-slate-900"
      @change="$emit('update:modelValue', value)"
    />
    <span
      :class="[
        'text-sm transition-colors',
        modelValue === value
          ? 'text-slate-200 font-medium'
          : 'text-slate-400 group-hover:text-slate-300'
      ]"
    >
      {{ label }}
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: null,
  },
  value: {
    type: [String, Number, Boolean],
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
});

defineEmits(['update:modelValue']);
</script>
