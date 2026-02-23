<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden relative group h-full flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
      <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Distribuição por Módulo</h3>
      <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Proporção de registros por área</p>
    </div>
    <div class="p-5 flex flex-col sm:flex-row items-center gap-6 flex-1 justify-center">
      <!-- Donut -->
      <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex-shrink-0 flex items-center justify-center group/donut">
        <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
          <!-- Background Circle -->
          <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800/50" fill="none" />
          
          <!-- Segments -->
          <circle
            v-for="(segment, index) in donutSegments"
            :key="segment.name"
            cx="50"
            cy="50"
            r="40"
            fill="none"
            stroke-width="8"
            stroke-linecap="round"
            :stroke="segment.color"
            :stroke-dasharray="segment.dashArray"
            :stroke-dashoffset="segment.dashOffset"
            class="transition-all duration-300 ease-out cursor-pointer origin-center hover:opacity-100"
            :class="[
              hoveredSegment === index ? 'scale-110 opacity-100 brightness-110 drop-shadow-[0_0_8px_rgba(0,0,0,0.5)]' : 'scale-100 opacity-90 hover:scale-105'
            ]"
            @mouseenter="hoveredSegment = index"
            @mouseleave="hoveredSegment = null"
          />
        </svg>

        <!-- Center Info -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-white dark:bg-slate-900 shadow-[inset_0_2px_10px_rgba(0,0,0,0.1)] flex flex-col items-center justify-center z-10 transition-transform duration-300"
               :class="{'scale-105': hoveredSegment !== null}">
            <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100 transition-colors duration-300"
                  :style="{ color: hoveredSegment !== null ? moduleDistribution[hoveredSegment].color : '' }">
              {{ hoveredSegment !== null ? moduleDistribution[hoveredSegment].value : totalRegistros.toLocaleString('pt-BR') }}
            </span>
            <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {{ hoveredSegment !== null ? moduleDistribution[hoveredSegment].name : 'Total' }}
            </span>
          </div>
        </div>
      </div>
      <!-- Legenda -->
      <div class="flex-1 space-y-3 w-full">
        <div
          v-for="(mod, index) in moduleDistribution"
          :key="mod.name"
          class="flex items-center justify-between gap-3 group cursor-pointer transition-all duration-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 rounded-lg"
          :class="{'bg-slate-50 dark:bg-slate-800/50 scale-[1.02] shadow-sm ring-1 ring-slate-100 dark:ring-slate-700': hoveredSegment === index}"
          @mouseenter="hoveredSegment = index"
          @mouseleave="hoveredSegment = null"
        >
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: mod.color }"></span>
            <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ mod.name }}</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ mod.value }}</span>
            <span class="text-xs text-slate-500 dark:text-slate-400 w-10 text-right">{{ mod.percent }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const hoveredSegment = ref(null);

const moduleDistribution = ref([
  { name: 'RAT', value: 892, percent: 35, color: '#06b6d4' },
  { name: 'Demandas', value: 637, percent: 25, color: '#f59e0b' },
  { name: 'Decretações', value: 510, percent: 20, color: '#ef4444' },
  { name: 'PAE', value: 306, percent: 12, color: '#10b981' },
  { name: 'Ajuda Humanitária', value: 204, percent: 8, color: '#8b5cf6' },
]);

const totalRegistros = computed(() => moduleDistribution.value.reduce((sum, m) => sum + m.value, 0));

const donutSegments = computed(() => {
  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  let accumulatedPercent = 0;

  return moduleDistribution.value.map((m, index) => {
    const percent = m.percent / 100;
    const dashArray = `${percent * circumference} ${circumference}`;
    const dashOffset = -accumulatedPercent * circumference;
    accumulatedPercent += percent;

    return {
      ...m,
      dashArray,
      dashOffset
    };
  });
});
</script>
