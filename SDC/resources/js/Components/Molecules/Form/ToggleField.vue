<template>
  <div class="flex items-center justify-between p-3 sm:p-4 rounded-lg bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-700/50">
    <div class="flex gap-2 sm:gap-3 items-start">
      <div v-if="icon" class="bg-purple-500/20 p-1.5 sm:p-2 rounded-lg flex-shrink-0">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" />
      </div>
      <div>
        <p class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200">
          {{ label }}
        </p>
        <p v-if="description" class="text-xs text-slate-500 mt-0.5 hidden sm:block">
          {{ description }}
        </p>
      </div>
    </div>
    <ToggleInput
      :model-value="modelValue"
      class="flex-shrink-0 ml-2 sm:ml-4"
      @update:model-value="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import ToggleInput from '@/Components/Atoms/Input/ToggleInput.vue';

defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: [Object, Function],
    default: null,
  },
});

defineEmits(['update:modelValue']);
</script>
