<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden h-full">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
      <div>
        <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Plano de Contingencia</h3>
        <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Municipios de Minas Gerais</p>
      </div>
      <Link :href="route('plancon.index')" class="text-xs text-blue-500 hover:text-blue-400 font-medium flex items-center gap-1">
        Ver detalhes
        <ChevronRightIcon class="w-4 h-4" />
      </Link>
    </div>

    <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="flex flex-col sm:flex-row items-center gap-4">
        <div class="relative w-32 h-32 sm:w-36 sm:h-36 flex-shrink-0">
          <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
            <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800/50" fill="none" />
            <circle
              v-for="(segment, index) in municipiosSegments"
              :key="'mun-' + index"
              cx="50" cy="50" r="40" fill="none" stroke-width="8" stroke-linecap="round"
              :stroke="segment.color"
              :stroke-dasharray="segment.dashArray"
              :stroke-dashoffset="segment.dashOffset"
              class="transition-all duration-300"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <span class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ stats.percentualComPlano }}%</span>
              <span class="block text-[9px] text-slate-500 dark:text-slate-400 uppercase tracking-wider">Com Plano</span>
            </div>
          </div>
        </div>
        <div class="space-y-2 flex-1">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
              <span class="text-xs text-slate-600 dark:text-slate-300">Com plano</span>
            </div>
            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ stats.municipiosComPlano }}</span>
          </div>
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
              <span class="text-xs text-slate-600 dark:text-slate-300">Sem plano</span>
            </div>
            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ stats.municipiosSemPlano }}</span>
          </div>
        </div>
      </div>

      <div class="flex flex-col sm:flex-row items-center gap-4">
        <div class="relative w-32 h-32 sm:w-36 sm:h-36 flex-shrink-0">
          <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
            <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800/50" fill="none" />
            <circle
              v-for="(segment, index) in planosSegments"
              :key="'plan-' + index"
              cx="50" cy="50" r="40" fill="none" stroke-width="8" stroke-linecap="round"
              :stroke="segment.color"
              :stroke-dasharray="segment.dashArray"
              :stroke-dashoffset="segment.dashOffset"
              class="transition-all duration-300"
            />
          </svg>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <span class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ stats.percentualRegulares }}%</span>
              <span class="block text-[9px] text-slate-500 dark:text-slate-400 uppercase tracking-wider">Regulares</span>
            </div>
          </div>
        </div>
        <div class="space-y-2 flex-1">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
              <span class="text-xs text-slate-600 dark:text-slate-300">Regular</span>
            </div>
            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ stats.planosRegulares }}</span>
          </div>
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
              <span class="text-xs text-slate-600 dark:text-slate-300">Irregular</span>
            </div>
            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">{{ stats.planosIrregulares }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRightIcon } from '@heroicons/vue/24/outline';

const stats = {
  totalMunicipios: 853,
  municipiosComPlano: 729,
  municipiosSemPlano: 124,
  percentualComPlano: 85.5,
  totalPlanos: 729,
  planosRegulares: 714,
  planosIrregulares: 15,
  percentualRegulares: 97.9,
};

const municipiosSegments = computed(() => {
  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  const data = [
    { percent: stats.percentualComPlano, color: '#3b82f6' },
    { percent: 100 - stats.percentualComPlano, color: '#f97316' },
  ];

  let accumulatedPercent = 0;
  return data.map((item) => {
    const percent = item.percent / 100;
    const dashArray = `${percent * circumference} ${circumference}`;
    const dashOffset = -accumulatedPercent * circumference;
    accumulatedPercent += percent;
    return { ...item, dashArray, dashOffset };
  });
});

const planosSegments = computed(() => {
  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  const data = [
    { percent: stats.percentualRegulares, color: '#3b82f6' },
    { percent: 100 - stats.percentualRegulares, color: '#f97316' },
  ];

  let accumulatedPercent = 0;
  return data.map((item) => {
    const percent = item.percent / 100;
    const dashArray = `${percent * circumference} ${circumference}`;
    const dashOffset = -accumulatedPercent * circumference;
    accumulatedPercent += percent;
    return { ...item, dashArray, dashOffset };
  });
});
</script>
