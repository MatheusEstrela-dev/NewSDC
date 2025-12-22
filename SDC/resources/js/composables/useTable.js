import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export function useTable(initialPagination = null, routeName = 'rat.index') {
  const pagination = ref(initialPagination);
  const sortColumn = ref(null);
  const sortDirection = ref('desc');

  const currentPage = computed(() => {
    return pagination.value?.current_page || 1;
  });

  const totalPages = computed(() => {
    return pagination.value?.last_page || 1;
  });

  const hasNextPage = computed(() => {
    return currentPage.value < totalPages.value;
  });

  const hasPreviousPage = computed(() => {
    return currentPage.value > 1;
  });

  function goToPage(page) {
    if (page < 1 || page > totalPages.value) return;
    
    router.get(route(routeName), { page }, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function nextPage() {
    if (hasNextPage.value) {
      goToPage(currentPage.value + 1);
    }
  }

  function previousPage() {
    if (hasPreviousPage.value) {
      goToPage(currentPage.value - 1);
    }
  }

  function sort(column) {
    if (sortColumn.value === column) {
      sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
      sortColumn.value = column;
      sortDirection.value = 'asc';
    }

    router.get(route(routeName), {
      sort: sortColumn.value,
      direction: sortDirection.value,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  }

  function updatePagination(newPagination) {
    pagination.value = { ...pagination.value, ...newPagination };
  }

  return {
    pagination,
    sortColumn,
    sortDirection,
    currentPage,
    totalPages,
    hasNextPage,
    hasPreviousPage,
    goToPage,
    nextPage,
    previousPage,
    sort,
    updatePagination,
  };
}

