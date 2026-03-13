import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useRatFilters(initialFilters = {}) {
  const filters = ref({ ...initialFilters });

  const hasActiveFilters = computed(() => {
    return Object.values(filters.value).some(value => {
      if (Array.isArray(value)) return value.length > 0;
      if (typeof value === 'object' && value !== null) {
        return Object.values(value).some(v => v !== '');
      }
      return value !== '' && value !== null && value !== undefined;
    });
  });

  function updateFilter(key, value) {
    filters.value[key] = value;
  }

  function updateFilters(newFilters) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function resetFilters() {
    filters.value = {};
  }

  function applyFilters() {
    router.get(route('rat.index'), filters.value, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function clearFilters() {
    resetFilters();
    router.get(route('rat.index'), {}, {
      preserveState: false,
      preserveScroll: false,
    });
  }

  return {
    filters,
    hasActiveFilters,
    updateFilter,
    updateFilters,
    resetFilters,
    applyFilters,
    clearFilters,
  };
}

