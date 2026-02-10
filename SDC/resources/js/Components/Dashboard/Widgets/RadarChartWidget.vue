<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden relative group h-full flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
      <div>
        <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Avaliação 360º</h3>
        <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Qualidade e Eficiência</p>
      </div>
    </div>
    
    <div class="p-4 flex items-center justify-center relative flex-1 min-h-[300px]">
       <VueApexCharts type="radar" height="100%" width="100%" :options="radarChartOptions" :series="radarChartSeries" />
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

// Props simulação
const radarMetrics = ref([
  { label: 'Eficiência', value: 85, fullMark: 100 },
  { label: 'Tempo Resp.', value: 70, fullMark: 100 },
  { label: 'Volume', value: 92, fullMark: 100 },
  { label: 'Qualidade', value: 88, fullMark: 100 },
  { label: 'Satisfação', value: 78, fullMark: 100 },
  { label: 'Cobertura', value: 95, fullMark: 100 },
]);

const radarChartSeries = computed(() => [{
  name: 'Avaliação',
  data: radarMetrics.value.map(m => m.value),
}]);

const radarChartOptions = computed(() => ({
  chart: {
    type: 'radar',
    height: 350,
    toolbar: { show: false },
    background: 'transparent',
    fontFamily: 'Inter, sans-serif',
    dropShadow: { enabled: true, blur: 1, left: 1, top: 1, opacity: 0.1 }
  },
  labels: radarMetrics.value.map(m => m.label),
  stroke: { width: 2, colors: ['#06b6d4'], dashArray: 0 },
  fill: { opacity: 0.2, colors: ['#06b6d4'] },
  markers: { size: 4, colors: ['#fff'], strokeColors: '#06b6d4', strokeWidth: 2, hover: { size: 7 } },
  yaxis: { show: false, min: 0, max: 100 },
  xaxis: {
    categories: radarMetrics.value.map(m => m.label),
    labels: {
      show: true,
      style: {
        colors: ['#64748b', '#64748b', '#64748b', '#64748b', '#64748b', '#64748b'],
        fontSize: '11px',
        fontFamily: 'Inter, sans-serif',
      }
    }
  },
  plotOptions: {
    radar: {
      size: 110,
      polygons: {
        strokeColors: '#e2e8f0', // dark: #334155
        connectorColors: '#e2e8f0',
      }
    }
  },
  theme: { mode: 'dark' },
  tooltip: {
    theme: 'dark',
    y: { formatter: (val) => val + '%' }
  }
}));
</script>
