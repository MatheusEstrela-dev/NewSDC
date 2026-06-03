<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col h-full relative group">
    
    <!-- Seamless Header (Floating) -->
    <div class="absolute top-0 left-0 right-0 p-6 z-20 flex flex-col sm:flex-row sm:items-start justify-between gap-4 pointer-events-none">
      <div>
        <h3 class="font-bold text-lg text-slate-900 dark:text-white tracking-tight flex items-center gap-2 drop-shadow-md">
          <span class="w-1 h-5 bg-gradient-to-b from-cyan-400 to-blue-500 rounded-full"></span>
          Tendência Mensal
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 ml-3 font-medium">Fluxo de Processos</p>
      </div>
      
      <!-- Floating Legend -->
      <div class="flex items-center gap-1 pointer-events-auto bg-white/50 dark:bg-slate-900/50 backdrop-blur-md p-1 rounded-lg border border-slate-200/50 dark:border-slate-700/50">
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-cyan-500/10 border border-cyan-500/20">
          <span class="w-2 h-2 rounded-full bg-cyan-500 shadow-[0_0_6px_currentColor]"></span>
          <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-300">Abertos</span>
        </div>
        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/20">
          <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_6px_currentColor]"></span>
          <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">Concluídos</span>
        </div>
      </div>
    </div>

    <!-- Chart Area -->
    <div class="relative w-full bg-gradient-to-b from-slate-50/30 to-white dark:from-slate-800/10 dark:to-slate-900 pt-16 pb-4 px-4 h-full flex-1 min-h-[320px]">
      <LazyChart type="area" height="100%" width="100%" :options="trendChartOptions" :series="trendChartSeries" />
    </div>

  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';

// Dados
const trendMonths = ref(['Set', 'Out', 'Nov', 'Dez', 'Jan', 'Fev']);
const trendAbertos = ref([42, 58, 52, 68, 75, 61]);
const trendConcluidos = ref([35, 48, 55, 60, 70, 58]);

// Séries
const trendChartSeries = computed(() => [
  { name: 'Abertos', data: trendAbertos.value },
  { name: 'Concluídos', data: trendConcluidos.value }
]);

// Opções
const trendChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeinout', speed: 800 },
    background: 'transparent',
    fontFamily: 'Inter, sans-serif'
  },
  colors: ['#06b6d4', '#10b981'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#334155',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: trendMonths.value,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
  },
  yaxis: {
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
  },
  legend: { show: false },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    }
  },
  tooltip: {
    theme: 'dark',
    x: { show: true },
    enabled: true,
    shared: true,
    intersect: false,
    followCursor: true,
    marker: { show: true }
  },
  crosshairs: {
    show: true,
    width: 1,
    stroke: { color: '#94a3b8', width: 1, dashArray: 4 }
  }
}));
</script>
