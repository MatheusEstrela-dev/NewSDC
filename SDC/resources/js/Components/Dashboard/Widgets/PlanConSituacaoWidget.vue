<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden h-full group hover:shadow-xl hover:border-blue-500/30 transition-all duration-300">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
      <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Situacao dos Planos Inseridos no Sistema</h3>
    </div>

    <div class="p-5 flex flex-col sm:flex-row items-center gap-6">
      <div class="relative w-40 h-40 flex-shrink-0 group/chart">
        <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
          <circle
            cx="50" cy="50" r="40"
            stroke="currentColor"
            stroke-width="10"
            class="text-slate-100 dark:text-slate-800/50"
            fill="none"
          />
          <circle
            v-for="(segment, index) in segments"
            :key="index"
            cx="50" cy="50" r="40"
            fill="none"
            stroke-width="10"
            stroke-linecap="round"
            :stroke="segment.color"
            :stroke-dasharray="segment.dashArray"
            :stroke-dashoffset="segment.dashOffset"
            class="transition-all duration-500 cursor-pointer hover:opacity-80"
            :class="{ 'filter drop-shadow-lg': hoveredIndex === index }"
            @mouseenter="hoveredIndex = index"
            @mouseleave="hoveredIndex = null"
          />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="text-center">
            <span class="text-2xl font-bold text-slate-900 dark:text-slate-100 transition-transform group-hover/chart:scale-110">
              {{ stats.percentualRegulares }}%
            </span>
            <span class="block text-[10px] text-slate-500 dark:text-slate-400 uppercase tracking-wider font-medium">
              Regulares
            </span>
          </div>
        </div>
      </div>

      <div class="flex-1 space-y-3 w-full">
        <div
          class="flex items-center justify-between p-3 rounded-lg transition-all duration-200 cursor-pointer"
          :class="hoveredIndex === 0 ? 'bg-blue-500/10 scale-[1.02]' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'"
          @mouseenter="hoveredIndex = 0"
          @mouseleave="hoveredIndex = null"
        >
          <div class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full bg-blue-500 shadow-lg shadow-blue-500/30"></span>
            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Planos em Situacao Regular</span>
          </div>
          <div class="text-right">
            <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ stats.planosRegulares }}</span>
            <span class="text-xs text-slate-400 ml-2">{{ stats.percentualRegulares }}%</span>
          </div>
        </div>

        <div
          class="flex items-center justify-between p-3 rounded-lg transition-all duration-200 cursor-pointer"
          :class="hoveredIndex === 1 ? 'bg-orange-500/10 scale-[1.02]' : 'hover:bg-slate-50 dark:hover:bg-slate-700/50'"
          @mouseenter="hoveredIndex = 1"
          @mouseleave="hoveredIndex = null"
        >
          <div class="flex items-center gap-3">
            <span class="w-3 h-3 rounded-full bg-orange-500 shadow-lg shadow-orange-500/30"></span>
            <span class="text-sm text-slate-700 dark:text-slate-300 font-medium">Planos em Situacao Irregular</span>
          </div>
          <div class="text-right">
            <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ stats.planosIrregulares }}</span>
            <span class="text-xs text-slate-400 ml-2">{{ (100 - stats.percentualRegulares).toFixed(1) }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const hoveredIndex = ref(null);

const stats = {
  totalPlanos: 729,
  planosRegulares: 714,
  planosIrregulares: 15,
  percentualRegulares: 97.9,
};

const segments = computed(() => {
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
