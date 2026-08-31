<template>
  <div>
    <Head title="Municipios sem Plano de Contingencia" />
    <MunicipiosListTemplate
      title="Municipios sem Plano"
      description="Lista de municipios que ainda nao possuem Plano de Contingencia cadastrado"
      :icon="ShieldCheckIcon"
      :municipios="municipios"
      :total-municipios="pagination?.total ?? municipios.length"
      :pagination="pagination"
      :filters="filters"
      :show-situacao="false"
      :show-data-atualizacao="false"
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
import { ShieldCheckIcon } from '@heroicons/vue/24/outline';

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
  router.get(route('plancon.municipios.sem'), filters, { preserveState: true, preserveScroll: true, replace: true });
};

const handleExport = () => {
  window.location.href = route('plancon.municipios.sem', { export: true });
};

const handleView = (municipio) => {
  router.visit(route('plancon.index', { municipio_id: municipio.id }));
};
</script>
