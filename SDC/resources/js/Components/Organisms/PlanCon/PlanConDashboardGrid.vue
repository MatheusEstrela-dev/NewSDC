<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row gap-4">
      <PlanConLinkCard
        :href="route('plancon.municipios.com')"
        :title="'Lista de Municipios com Plano de Contingencia'"
        :icon="DocumentTextIcon"
        variant="default"
        highlightWord="com"
        class="flex-1"
      />
      <PlanConLinkCard
        :href="route('plancon.municipios.sem')"
        :title="'Lista de Municipios sem Plano de Contingencia'"
        :icon="DocumentTextIcon"
        variant="default"
        highlightWord="sem"
        class="flex-1"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <PlanConPieChart
        title="Plano de Contingencia Municipios Mineiros"
        :data="municipiosChartData"
        :centerValue="stats.percentualComPlano + '%'"
        centerLabel="Com Plano"
      />
      <PlanConPieChart
        title="Situacao dos Planos Inseridos no Sistema"
        :data="planosChartData"
        :centerValue="stats.percentualRegulares + '%'"
        centerLabel="Regulares"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';
import PlanConLinkCard from '../../Molecules/PlanCon/PlanConLinkCard.vue';
import PlanConPieChart from './PlanConPieChart.vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
});

const municipiosChartData = computed(() => [
  {
    name: 'Municipios com plano',
    value: props.stats.municipiosComPlano,
    percent: props.stats.percentualComPlano,
    color: '#3b82f6',
  },
  {
    name: 'Municipios Sem Plano',
    value: props.stats.municipiosSemPlano,
    percent: parseFloat((100 - props.stats.percentualComPlano).toFixed(1)),
    color: '#f97316',
  },
]);

const planosChartData = computed(() => [
  {
    name: 'Planos em Situacao Regular',
    value: props.stats.planosRegulares,
    percent: props.stats.percentualRegulares,
    color: '#3b82f6',
  },
  {
    name: 'Planos em Situacao Irregular',
    value: props.stats.planosIrregulares,
    percent: parseFloat((100 - props.stats.percentualRegulares).toFixed(1)),
    color: '#f97316',
  },
]);
</script>
