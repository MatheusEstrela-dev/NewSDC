import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useRatFilters(initialFilters = {}) {
  const filters = ref({ ...initialFilters });

  const temFiltrosAtivos = computed(() => {
    return Object.values(filters.value).some(value => {
      if (Array.isArray(value)) return value.length > 0;
      if (typeof value === 'object' && value !== null) {
        return Object.values(value).some(v => v !== '');
      }
      return value !== '' && value !== null && value !== undefined;
    });
  });

  function atualizarFiltro(key, value) {
    filters.value[key] = value;
  }

  function atualizarFiltros(newFilters) {
    filters.value = { ...filters.value, ...newFilters };
  }

  function limparFiltros() {
    filters.value = {};
  }

  function aplicarFiltros() {
    router.get(route('rat.index'), filters.value, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function limparTodosFiltros() {
    limparFiltros();
    router.get(route('rat.index'), {}, {
      preserveState: false,
      preserveScroll: false,
    });
  }

  return {
    filters,
    temFiltrosAtivos,
    atualizarFiltro,
    atualizarFiltros,
    limparFiltros,
    aplicarFiltros,
    limparTodosFiltros,
  };
}

