<template>
  <Head title="Estoque" />
  <EstoqueIndexTemplate
    :produtos="produtos"
    :kits="kits"
    :movimentacoes="movimentacoes"
    :alertas="alertas"
    :active-section="activeSection"
    :excel-sheets="excelSheets"
    :statistics="statistics"
    :filters="filters"
    :filter-options="filterOptions"
    :loading="loading"
    :can-create="can('estoque.produtos.create')"
    :can-edit="can('estoque.produtos.edit')"
    :can-delete="can('estoque.produtos.delete')"
    :can-export="can('estoque.produtos.export')"
    :can-move="can('estoque.movimentacoes.create')"
    :can-assemble="can('estoque.kits.assemble')"
    @filter-change="handleFilterChange"
    @clear-filters="handleClearFilters"
  />
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EstoqueIndexTemplate from '@/Templates/Estoque/EstoqueIndexTemplate.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  produtos: {
    type: Array,
    default: () => [],
  },
  kits: {
    type: Array,
    default: () => [],
  },
  movimentacoes: {
    type: Array,
    default: () => [],
  },
  alertas: {
    type: Array,
    default: () => [],
  },
  activeSection: {
    type: String,
    default: 'dashboard',
  },
  excelSheets: {
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
  router.get(route('estoque.index'), filters, {
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
