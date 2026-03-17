<template>
  <div class="flex flex-wrap gap-2">
    <button
      v-for="filter in quickFilters"
      :key="filter.key"
      type="button"
      class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-all duration-200"
      :class="isActive(filter.key)
        ? 'bg-primary-500 text-white border-primary-500 shadow-md'
        : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-600 hover:border-primary-400 dark:hover:border-primary-500 hover:bg-primary-50 dark:hover:bg-primary-900/20'"
      @click="toggleFilter(filter)"
    >
      {{ filter.label }}
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  quickFilters: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue', 'select']);

const selectedIds = computed(() => props.modelValue || []);

function isActive(filterKey) {
  const filter = props.quickFilters.find(f => f.key === filterKey);
  if (!filter) return false;
  return filter.ids.every(id => selectedIds.value.includes(id));
}

function toggleFilter(filter) {
  const currentIds = [...selectedIds.value];
  const isCurrentlyActive = isActive(filter.key);

  let newIds;
  if (isCurrentlyActive) {
    newIds = currentIds.filter(id => !filter.ids.includes(id));
  } else {
    const idsToAdd = filter.ids.filter(id => !currentIds.includes(id));
    newIds = [...currentIds, ...idsToAdd];
  }

  emit('update:modelValue', newIds);
  emit('select', { filter, ids: newIds, active: !isCurrentlyActive });
}
</script>
