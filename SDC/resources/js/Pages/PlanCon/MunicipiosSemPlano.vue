<template>
  <div>
    <Head title="Municipios sem Plano de Contingencia" />
    <MunicipiosListTemplate
      title="Municipios sem Plano"
      description="Lista de municipios que ainda nao possuem Plano de Contingencia cadastrado"
      :icon="ExclamationCircleIcon"
      :municipios="effectiveMunicipios"
      :total-municipios="stats.municipiosSemPlano"
      :show-situacao="false"
      :show-data-atualizacao="false"
      :can-export="can('plancon.export')"
      @export="handleExport"
      @view="handleView"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MunicipiosListTemplate from '@/Templates/PlanCon/MunicipiosListTemplate.vue';
import { ExclamationCircleIcon } from '@heroicons/vue/24/outline';

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
  stats: {
    type: Object,
    default: () => ({
      municipiosSemPlano: 124,
    }),
  },
});

const effectiveMunicipios = computed(() => {
  if (props.municipios && props.municipios.length > 0) {
    return props.municipios;
  }
  return generateMockMunicipios(124);
});

const generateMockMunicipios = (count) => {
  const nomes = ['Alto Jequitiba', 'Bandeira do Sul', 'Caiana', 'Divino das Laranjeiras', 'Estrela do Sul', 'Ferros', 'Goiana', 'Heliodora', 'Ipaba', 'Jacinto'];
  return Array.from({ length: Math.min(count, 100) }, (_, i) => ({
    id: i + 1000,
    nome: `${nomes[i % nomes.length]} ${Math.floor(i / nomes.length) || ''}`.trim(),
    codigoIbge: `31${String(i + 1000).padStart(5, '0')}`,
    temPlano: false,
    situacaoPlano: null,
    dataUltimaAtualizacao: null,
  }));
};

const handleExport = () => {
  window.location.href = route('plancon.municipios.sem', { export: true });
};

const handleView = (municipio) => {
  router.visit(route('plancon.municipios.sem', { id: municipio.id }));
};
</script>
