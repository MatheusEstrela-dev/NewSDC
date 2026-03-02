import { ref, computed, watch } from 'vue';

/**
 * Composable para gerenciamento de paginacao client-side
 * @param {Ref<Array>} items - Array reativo de itens para paginar
 * @param {number} perPage - Numero de itens por pagina (default: 15)
 * @returns {Object} - currentPage, pagination, paginatedItems, goToPage, nextPage, prevPage, resetPage
 */
export function usePagination(items = ref([]), perPage = 15) {
  const currentPage = ref(1);

  const pagination = computed(() => {
    const total = items.value?.length || 0;
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const safePage = Math.min(Math.max(1, currentPage.value), lastPage);

    return {
      current_page: safePage,
      last_page: lastPage,
      per_page: perPage,
      total,
      from: total > 0 ? (safePage - 1) * perPage + 1 : 0,
      to: Math.min(safePage * perPage, total)
    };
  });

  const paginatedItems = computed(() => {
    const p = pagination.value;
    const start = (p.current_page - 1) * p.per_page;
    return items.value?.slice(start, start + p.per_page) || [];
  });

  const goToPage = (page) => {
    const lastPage = pagination.value.last_page;
    currentPage.value = Math.min(Math.max(1, page), lastPage);
  };

  const nextPage = () => {
    if (currentPage.value < pagination.value.last_page) {
      currentPage.value++;
    }
  };

  const prevPage = () => {
    if (currentPage.value > 1) {
      currentPage.value--;
    }
  };

  const resetPage = () => {
    currentPage.value = 1;
  };

  // Reset para pagina 1 quando itens mudam
  watch(() => items.value?.length, () => {
    if (currentPage.value > pagination.value.last_page) {
      resetPage();
    }
  });

  return {
    currentPage,
    pagination,
    paginatedItems,
    goToPage,
    nextPage,
    prevPage,
    resetPage
  };
}
