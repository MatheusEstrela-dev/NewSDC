<template>
  <div
    class="hidden md:flex items-center gap-1 bg-white dark:bg-slate-800/50 rounded-lg p-1 border border-slate-300 dark:border-slate-700/50"
  >
    <button
      v-for="option in options"
      :key="option.value"
      @click="$emit('update:modelValue', option.value)"
      :class="[
        'px-3 py-1.5 rounded text-xs font-medium transition-all',
        modelValue === option.value
          ? 'bg-blue-600 text-white shadow-sm'
          : 'text-slate-600 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
      ]"
      :title="option.title"
    >
      {{ option.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: {
    type: String,
    required: true
  },
  options: {
    type: Array,
    default: () => [
      { value: 'grid', label: 'Grade', title: 'Visualizacao em Grade' },
      { value: 'table', label: 'Tabela', title: 'Visualizacao em Tabela' }
    ]
  }
});

defineEmits(['update:modelValue']);
</script>
