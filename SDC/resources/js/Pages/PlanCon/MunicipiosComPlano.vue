<template>
  <div>
    <Head title="Municipios com Plano de Contingencia" />
    <MunicipiosListTemplate
      title="Municipios com Plano"
      description="Lista de municipios que possuem Plano de Contingencia cadastrado"
      :icon="CheckCircleIcon"
      :municipios="municipios"
      :total-municipios="pagination?.total ?? municipios.length"
      :pagination="pagination"
      :filters="filters"
      :show-situacao="true"
      :show-data-atualizacao="true"
      :can-export="can('plancon.export')"
      @export="handleExport"
      @filter="handleFilter"
      @view="handleView"
    />
  </div>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MunicipiosListTemplate from '@/Templates/PlanCon/MunicipiosListTemplate.vue';
import { CheckCircleIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  municipios: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: null,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const handleFilter = (filters) => {
  router.get(route('plancon.municipios.com'), filters, { preserveState: true, preserveScroll: true, replace: true });
};

const handleExport = () => {
  window.location.href = route('plancon.municipios.com', { export: true });
};

const handleView = (municipio) => {
  router.visit(route('plancon.index', { municipio_id: municipio.id }));
};
</script>
