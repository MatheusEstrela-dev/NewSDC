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
  turnoAtivo: {
    type: Object,
    default: null,
  },
  turnoPendente: {
    type: Object,
    default: null,
  },
  canEncerrar: {
    type: Boolean,
    default: false,
  },
  canAceitar: {
    type: Boolean,
    default: false,
  },
  canRelatorio: {
    type: Boolean,
    default: false,
  },
});

const handleView = (id) => {
  // TODO: Implementar visualização detalhada
};

const handleEdit = (id) => {
  // TODO: Implementar edição
};

const handleFilter = (filters) => {
  // Reload parcial: turnoAtivo/turnoPendente fazem query propria e nao
  // dependem do filtro da listagem. Sem o `only`, toda troca de filtro
  // recalcularia os dois a cada visita completa do Inertia.
  router.visit(route('plantao.index'), {
    data: filters,
    only: ['plantoes', 'filters'],
    preserveState: true,
    replace: true,
  });
};

const handleAbrirPlantao = (data) => {
  // TODO: Implementar POST para criar plantão
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
    :turno-ativo="turnoAtivo"
    :turno-pendente="turnoPendente"
    :can-encerrar="canEncerrar"
    :can-aceitar="canAceitar"
    :can-relatorio="canRelatorio"
    @view="handleView"
    @edit="handleEdit"
    @filter="handleFilter"
    @abrir-plantao="handleAbrirPlantao"
  />
</template>
