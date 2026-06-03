<template>
  <div v-if="activeFilters.length > 0" class="flex flex-wrap items-center gap-2 mb-4">
    <span class="text-sm text-slate-500 dark:text-slate-400">Filtros ativos:</span>
    <TransitionGroup name="filter-tag">
      <span
        v-for="filter in activeFilters"
        :key="filter.key"
        class="inline-flex items-center gap-1.5 px-3 py-1 text-sm rounded-full
               bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300
               border border-primary-200 dark:border-primary-700/50"
      >
        <span class="font-medium">{{ filter.label }}:</span>
        <span>{{ filter.displayValue }}</span>
        <button
          type="button"
          class="ml-1 p-0.5 rounded-full hover:bg-primary-200 dark:hover:bg-primary-800 transition-colors"
          @click="$emit('remove', filter.key)"
          :aria-label="`Remover filtro ${filter.label}`"
        >
          <XMarkIcon class="w-3.5 h-3.5" />
        </button>
      </span>
    </TransitionGroup>
    <button
      v-if="activeFilters.length > 1"
      type="button"
      class="text-sm text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300
             underline underline-offset-2 transition-colors"
      @click="$emit('clear-all')"
    >
      Limpar todos
    </button>
  </div>
</template>

<script setup>
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

defineProps({
  activeFilters: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['remove', 'clear-all']);
</script>

<style scoped>
.filter-tag-enter-active,
.filter-tag-leave-active {
  transition: all 0.2s ease;
}

.filter-tag-enter-from,
.filter-tag-leave-to {
  opacity: 0;
  transform: scale(0.9);
}
</style>
