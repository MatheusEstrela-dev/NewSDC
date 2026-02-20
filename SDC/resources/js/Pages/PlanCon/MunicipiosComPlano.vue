<template>
  <div>
    <Head title="Municipios com Plano de Contingencia" />
    <MunicipiosListTemplate
      title="Municipios com Plano"
      description="Lista de municipios que possuem Plano de Contingencia cadastrado"
      :icon="CheckCircleIcon"
      :municipios="effectiveMunicipios"
      :total-municipios="stats.municipiosComPlano"
      :show-situacao="true"
      :show-data-atualizacao="true"
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
  stats: {
    type: Object,
    default: () => ({
      municipiosComPlano: 729,
    }),
  },
});

const effectiveMunicipios = computed(() => {
  if (props.municipios && props.municipios.length > 0) {
    return props.municipios;
  }
  return generateMockMunicipios(729, true);
});

const generateMockMunicipios = (count, comPlano) => {
  const nomes = ['Belo Horizonte', 'Uberlandia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros', 'Ribeirão das Neves', 'Uberaba', 'Governador Valadares', 'Ipatinga'];
  return Array.from({ length: Math.min(count, 100) }, (_, i) => ({
    id: i + 1,
    nome: `${nomes[i % nomes.length]} ${Math.floor(i / nomes.length) || ''}`.trim(),
    codigoIbge: `31${String(i + 1).padStart(5, '0')}`,
    temPlano: comPlano,
    situacaoPlano: comPlano ? (Math.random() > 0.02 ? 'regular' : 'irregular') : null,
    dataUltimaAtualizacao: comPlano ? new Date(Date.now() - Math.random() * 365 * 24 * 60 * 60 * 1000).toISOString() : null,
  }));
};

const handleExport = () => {
  window.location.href = route('plancon.municipios.com', { export: true });
};

const handleView = (municipio) => {
  router.visit(route('plancon.municipios.com', { id: municipio.id }));
};
</script>
