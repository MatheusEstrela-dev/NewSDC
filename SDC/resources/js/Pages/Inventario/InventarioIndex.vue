<template>
  <Head title="Inventario" />
  <InventarioIndexTemplate
    :equipamentos="equipamentos.data || []"
    :pagination="equipamentos"
    :statistics="statistics"
    :filters="filters"
    :filter-options="filterOptions"
    :loading="loading"
    :can-create="can('inventario.equipamentos.create')"
    :can-edit="can('inventario.equipamentos.edit')"
    :can-delete="can('inventario.equipamentos.delete')"
    @filter-change="handleFilterChange"
    @clear-filters="handleClearFilters"
    @page-change="handlePageChange"
    @create="openCreate"
    @edit="openEdit"
    @delete="removeEquipamento"
  />
  <EquipamentoFormModal
    :show="formOpen"
    :equipamento="selectedEquipamento"
    :categorias="filterOptions.categorias || []"
    @close="formOpen = false"
  />
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InventarioIndexTemplate from '@/Templates/Inventario/InventarioIndexTemplate.vue';
import EquipamentoFormModal from '@/Components/Organisms/Inventario/EquipamentoFormModal.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  equipamentos: {
    type: Object,
    default: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
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
const formOpen = ref(false);
const selectedEquipamento = ref(null);

function visitIndex(filters = {}, page = 1) {
  loading.value = true;
  router.get(route('inventario.index'), { ...filters, page }, {
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

function handlePageChange(page) {
  visitIndex(props.filters, page);
}

function openCreate() {
  selectedEquipamento.value = null;
  formOpen.value = true;
}

function openEdit(equipamento) {
  selectedEquipamento.value = equipamento;
  formOpen.value = true;
}

function removeEquipamento(equipamento) {
  if (!window.confirm(`Remover o equipamento ${equipamento.patrimonio}?`)) return;
  router.delete(`/inventario/equipamentos/${equipamento.id}`, { preserveScroll: true });
}
</script>
