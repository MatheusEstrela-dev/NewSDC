<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 overflow-hidden h-full flex flex-col">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
      <div>
        <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Atendimentos por Mês</h3>
        <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Últimos 6 meses - todos os módulos</p>
      </div>
      <div class="flex gap-1 bg-slate-100 dark:bg-slate-700/50 rounded-lg p-0.5">
        <button
          v-for="period in ['6M', '12M']"
          :key="period"
          :class="[
            'px-2.5 py-1 text-xs font-medium rounded-md transition-colors',
            barPeriod === period
              ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-white shadow-sm'
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
          ]"
          @click="barPeriod = period"
        >
          {{ period }}
        </button>
      </div>
    </div>
    <div class="p-5 relative flex-1 min-h-[250px] flex items-end justify-center">
      <!-- Custom Tooltip for Bars -->
      <div 
        v-if="hoveredBarIndex !== null"
        class="absolute z-50 pointer-events-none bg-slate-900/95 text-white px-3 py-1.5 rounded-lg shadow-xl border border-slate-700 text-xs transition-colors duration-200 whitespace-nowrap"
        :style="{ 
          left: `${(30 + hoveredBarIndex * ((600 - 60) / (activeBarData.length - 1))) / 6}%`, 
          top: `${200 - (activeBarData[hoveredBarIndex].value / maxBarValue) * 160 - 40}px`,
          transform: 'translateX(-50%)'
        }"
      >
        <div class="font-bold text-center">{{ activeBarData[hoveredBarIndex].label }}</div>
        <div class="text-cyan-400 font-bold text-sm text-center">{{ activeBarData[hoveredBarIndex].value }} <span class="text-[10px] text-slate-400 font-normal">atendimentos</span></div>
      </div>

      <svg class="w-full h-full overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none">
        <defs>
          <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#06b6d4" stop-opacity="1" />
            <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.3" />
          </linearGradient>
          <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="2" result="coloredBlur" />
            <feMerge>
              <feMergeNode in="coloredBlur" />
              <feMergeNode in="SourceGraphic" />
            </feMerge>
          </filter>
        </defs>

        <!-- Grid lines -->
        <line v-for="i in 4" :key="'grid-bar-'+i" x1="0" :y1="i * 40" :x2="600" :y2="i * 40" stroke="currentColor" class="text-slate-100 dark:text-slate-700/50" stroke-width="1" stroke-dasharray="4 4" />
        
        <!-- Bars -->
        <g v-for="(item, index) in activeBarData" :key="item.label + barPeriod">
          <rect
            :x="30 + index * ((600 - 60) / (activeBarData.length - 1)) - (activeBarData.length > 6 ? 8 : 12)"
            :y="200 - (item.value / maxBarValue) * 160"
            :width="activeBarData.length > 6 ? 16 : 24"
            :height="(item.value / maxBarValue) * 160"
            rx="4"
            fill="url(#barGradient)"
            class="transition-colors duration-500 cubic-bezier(0.4, 0, 0.2, 1) cursor-pointer"
            :class="[
              hoveredBarIndex === index ? 'opacity-100 filter url(#glow)' : 'opacity-80 hover:opacity-100'
            ]"
            :style="{ 
              transformOrigin: 'bottom',
              animation: `growUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards ${index * 50}ms`
            }"
            @mouseenter="hoveredBarIndex = index"
            @mouseleave="hoveredBarIndex = null"
          />
          
          <text
            :x="30 + index * ((600 - 60) / (activeBarData.length - 1))"
            y="215"
            text-anchor="middle"
            class="text-[10px] font-medium transition-colors duration-200"
            :class="hoveredBarIndex === index ? 'fill-cyan-500 font-bold' : 'fill-slate-400 dark:fill-slate-500'"
          >
            {{ item.label }}
          </text>
        </g>
      </svg>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const barPeriod = ref('6M');
const hoveredBarIndex = ref(null);

const barData6M = ref([
  { label: 'Set', value: 45 },
  { label: 'Out', value: 62 },
  { label: 'Nov', value: 58 },
  { label: 'Dez', value: 71 },
  { label: 'Jan', value: 83 },
  { label: 'Fev', value: 47 },
]);

const barData12M = ref([
  { label: 'Mar', value: 32 },
  { label: 'Abr', value: 38 },
  { label: 'Mai', value: 41 },
  { label: 'Jun', value: 55 },
  { label: 'Jul', value: 48 },
  { label: 'Ago', value: 39 },
  { label: 'Set', value: 45 },
  { label: 'Out', value: 62 },
  { label: 'Nov', value: 58 },
  { label: 'Dez', value: 71 },
  { label: 'Jan', value: 83 },
  { label: 'Fev', value: 47 },
]);

const activeBarData = computed(() => barPeriod.value === '6M' ? barData6M.value : barData12M.value);
const maxBarValue = computed(() => Math.max(...activeBarData.value.map(b => b.value)));
</script>
<style scoped>
@keyframes growUp {
  from { transform: scaleY(0); opacity: 0; }
  to { transform: scaleY(1); opacity: 1; }
}
</style>
