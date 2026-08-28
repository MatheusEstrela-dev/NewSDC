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
  // Listas: pode haver mais de um turno ATIVO (periodos diferentes) e mais de
  // um PENDENTE_ACEITE, porque abrir turno nao bloqueia o pendente (spec 4.2).
  turnosAtivos: {
    type: Array,
    default: () => [],
  },
  turnosPendentes: {
    type: Array,
    default: () => [],
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
  canEscala: {
    type: Boolean,
    default: false,
  },
});

const handleView = (id) => {
  router.visit(route('plantao.show', id));
};

const handleEdit = (id) => {
  router.visit(route('plantao.edit', id));
};

const handleFilter = (filters) => {
  // Reload parcial: turnosAtivos/turnosPendentes fazem query propria e nao
  // dependem do filtro da listagem. Sem o `only`, toda troca de filtro
  // recalcularia os dois a cada visita completa do Inertia.
  router.visit(route('plantao.index'), {
    data: filters,
    only: ['plantoes', 'filters'],
    preserveState: true,
    replace: true,
  });
};

const handleAbrirPlantao = (dados) => {
  // O plantonista responsavel e o usuario autenticado, resolvido no
  // PassagemAbrirController: nao viaja no payload.
  router.post(route('plantao.passagem.abrir'), {
    data: dados.data,
    periodo: dados.periodo,
  }, {
    preserveScroll: true,
  });
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
    :turnos-ativos="turnosAtivos"
    :turnos-pendentes="turnosPendentes"
    :can-encerrar="canEncerrar"
    :can-aceitar="canAceitar"
    :can-relatorio="canRelatorio"
    :can-escala="canEscala"
    @view="handleView"
    @edit="handleEdit"
    @filter="handleFilter"
    @abrir-plantao="handleAbrirPlantao"
  />
</template>
