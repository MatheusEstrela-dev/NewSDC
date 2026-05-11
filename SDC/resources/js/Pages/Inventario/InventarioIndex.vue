<template>
  <Head title="Inventario" />
  <InventarioIndexTemplate
    :equipamentos="equipamentos"
    :statistics="statistics"
    :filters="filters"
    :filter-options="filterOptions"
    :loading="loading"
    :can-create="can('inventario.equipamentos.create')"
    :can-edit="can('inventario.equipamentos.edit')"
    :can-delete="can('inventario.equipamentos.delete')"
    @filter-change="handleFilterChange"
    @clear-filters="handleClearFilters"
  />
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InventarioIndexTemplate from '@/Templates/Inventario/InventarioIndexTemplate.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  equipamentos: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const { can } = usePermissions();
const loading = ref(false);

function visitIndex(filters = {}) {
  loading.value = true;
  router.get(route('inventario.index'), filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
}

function handleFilterChange(filters) {
  visitIndex(filters);
}

function handleClearFilters() {
  visitIndex();
}
</script>
