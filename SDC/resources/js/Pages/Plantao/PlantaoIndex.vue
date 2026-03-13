<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlantaoIndexTemplate from '@/Templates/Plantao/PlantaoIndexTemplate.vue';
import { router } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  plantoes: {
    type: Object,
    required: true,
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

const handleView = (id) => {
  // TODO: Implementar visualização detalhada
  console.log('Visualizar plantão', id);
};

const handleEdit = (id) => {
  // TODO: Implementar edição
  console.log('Editar plantão', id);
};

const handleFilter = (filters) => {
  router.visit(route('plantao.index'), {
    data: filters,
    preserveState: true,
  });
};

const handleAbrirPlantao = (data) => {
  // TODO: Implementar POST para criar plantão
  console.log('Abrir plantão:', data);
};
</script>

<template>
  <PlantaoIndexTemplate
    :plantoes="plantoes.data"
    :statistics="statistics"
    :pagination="plantoes.pagination"
    :filters="filters"
    :filter-options="filterOptions"
    :can-create="can('plantao.turnos.create')"
    :can-edit="can('plantao.turnos.edit')"
    :can-delete="can('plantao.turnos.delete')"
    :can-export="can('plantao.turnos.export')"
    @view="handleView"
    @edit="handleEdit"
    @filter="handleFilter"
    @abrir-plantao="handleAbrirPlantao"
  />
</template>
