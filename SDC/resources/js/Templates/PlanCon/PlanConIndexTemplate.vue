<template>
  <div class="plancon-index-container">
    <PageHeader
      title="Plano de Contingencia"
      description="Gestao de Planos de Contingencia dos Municipios Mineiros"
      :icon="ShieldCheckIcon"
      variant="gradient"
    >
      <template #actions>
        <div class="flex min-w-0 flex-wrap items-center gap-2 sm:gap-3">
          <Button
            v-if="canExport"
            variant="success"
            size="md"
            :icon="ArrowDownTrayIcon"
            icon-position="left"
            @click="$emit('export')"
          >
            <span class="hidden sm:inline">Exportar</span>
          </Button>

          <Button
            v-if="canUpload"
            variant="primary"
            size="md"
            :icon="ArrowUpTrayIcon"
            icon-position="left"
            @click="$emit('upload')"
          >
            <span class="hidden sm:inline">Upload Plano</span>
            <span class="sm:hidden">Upload</span>
          </Button>
        </div>
      </template>
    </PageHeader>

    <div class="mt-6 space-y-6">
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <PlanConStatCard
          title="Total Municipios"
          :value="stats.totalMunicipios"
          :icon="MapPinIcon"
          variant="info"
        />
        <PlanConStatCard
          title="Com Plano"
          :value="stats.municipiosComPlano"
          :percentage="stats.percentualComPlano"
          :icon="CheckCircleIcon"
          variant="success"
        />
        <PlanConStatCard
          title="Sem Plano"
          :value="stats.municipiosSemPlano"
          :icon="ExclamationCircleIcon"
          variant="warning"
        />
        <PlanConStatCard
          title="Total Planos"
          :value="stats.totalPlanos"
          :icon="DocumentTextIcon"
          variant="primary"
        />
      </div>

      <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
        <PlanConLinkCard
          :href="route('plancon.municipios.com')"
          title="Lista de Municipios com Plano de Contingencia"
          :icon="DocumentTextIcon"
          variant="default"
          highlightWord="com"
          class="flex-1"
        />
        <PlanConLinkCard
          :href="route('plancon.municipios.sem')"
          title="Lista de Municipios sem Plano de Contingencia"
          :icon="DocumentTextIcon"
          variant="default"
          highlightWord="sem"
          class="flex-1"
        />
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
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

      <div
        v-if="recentActivities && recentActivities.length > 0"
        class="rounded-xl border border-slate-700/50 bg-slate-800/60 overflow-hidden"
      >
        <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-slate-700/50">
          <h3 class="font-semibold text-slate-200">Atividade Recente</h3>
        </div>
        <div class="divide-y divide-slate-700/50">
          <div
            v-for="activity in recentActivities"
            :key="activity.id"
            class="px-4 py-3 sm:px-5 sm:py-4 flex items-center gap-3 hover:bg-slate-700/30 transition-colors"
          >
            <div class="w-2 h-2 rounded-full" :class="getActivityColor(activity.type)"></div>
            <div class="flex-1 min-w-0">
              <p class="text-sm text-slate-200 truncate">{{ activity.description }}</p>
              <p class="text-xs text-slate-400">{{ activity.date }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PlanConStatCard from '@/Components/Molecules/PlanCon/PlanConStatCard.vue';
import PlanConLinkCard from '@/Components/Molecules/PlanCon/PlanConLinkCard.vue';
import PlanConPieChart from '@/Components/Organisms/PlanCon/PlanConPieChart.vue';
import {
  ShieldCheckIcon,
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  MapPinIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  recentActivities: {
    type: Array,
    default: () => [],
  },
  canExport: {
    type: Boolean,
    default: false,
  },
  canUpload: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['export', 'upload']);

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

const getActivityColor = (type) => {
  const colors = {
    created: 'bg-emerald-400',
    updated: 'bg-blue-400',
    deleted: 'bg-red-400',
    default: 'bg-slate-400',
  };
  return colors[type] || colors.default;
};
</script>

<style scoped>
.plancon-index-container {
  @apply min-h-screen;
}
</style>
