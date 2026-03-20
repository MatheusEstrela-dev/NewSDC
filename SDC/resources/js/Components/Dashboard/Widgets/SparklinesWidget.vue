<template>
  <div class="space-y-4 h-full">
    <div
      v-for="mod in moduleSparklines"
      :key="mod.name"
      class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 p-4 transition-colors duration-300 cursor-pointer group overflow-hidden relative"
      @click="$emit('select-module', mod)"
    >
      <!-- Background Decoration -->
      <div class="absolute inset-0 bg-gradient-to-br from-transparent via-transparent opacity-0 group-hover:opacity-10 transition-opacity duration-500" :class="'to-' + variantToColorClass(mod.variant)"></div>
      
      <div class="flex items-center justify-between gap-3 relative z-10">
        <div class="flex items-center gap-3 min-w-0">
          <div :class="sparklineIconClasses(mod.variant)" class="group-hover:scale-110 transition-transform duration-300">
            <component :is="mod.icon" class="w-4 h-4" />
          </div>
          <div class="min-w-0">
            <p class="text-sm font-bold text-slate-900 dark:text-slate-200 truncate">{{ mod.name }}</p>
            <div class="flex items-center gap-1.5 mt-0.5">
              <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ mod.value }}</span>
              <span :class="trendClasses(mod.trend)" class="!text-[10px] animate-pulse">
                <svg v-if="mod.trend > 0" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                </svg>
                <svg v-else class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>
                {{ Math.abs(mod.trend) }}%
              </span>
            </div>
          </div>
        </div>
        <!-- Mini Sparkline SVG Animado -->
        <svg class="w-20 h-10 flex-shrink-0 drop-shadow-sm group-hover:drop-shadow-[0_0_8px_rgba(var(--spark-color),0.5)] transition-colors duration-500" viewBox="0 0 80 32" fill="none" :style="`--spark-color: ${mod.variant === 'info' ? '6,182,212' : mod.variant === 'warning' ? '245,158,11' : '239,68,68'}`">
          <polyline
            :points="sparklinePoints(mod.data)"
            fill="none"
            :stroke="sparklineColor(mod.variant)"
            :stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            class="trend-line-path trend-line"
          />
          <polyline
            :points="sparklineAreaPoints(mod.data)"
            :fill="sparklineAreaFill(mod.variant)"
            stroke="none"
            class="opacity-50 group-hover:opacity-80 transition-opacity"
          />
        </svg>
      </div>
    </div>
  </div>
</template>

<script setup>
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import { markRaw, ref } from 'vue';

defineEmits(['select-module']);

const moduleSparklines = ref([
  { name: 'RAT', value: 156, trend: 18, variant: 'info', icon: markRaw(DocumentTextIcon), data: [12, 19, 15, 22, 18, 25, 20] },
  { name: 'Demandas', value: 43, trend: -5, variant: 'warning', icon: markRaw(ClipboardDocumentListIcon), data: [8, 12, 10, 7, 9, 6, 8] },
  { name: 'Decretações', value: 28, trend: 32, variant: 'danger', icon: markRaw(ExclamationTriangleIcon), data: [3, 5, 4, 8, 6, 10, 9] },
  { name: 'PAE', value: 12, trend: 5, variant: 'success', icon: markRaw(CheckCircleIcon), data: [2, 4, 3, 5, 4, 6, 5] },
  { name: 'Ajuda Humanitária', value: 204, trend: 15, variant: 'primary', icon: markRaw(HeartIcon), data: [15, 25, 20, 30, 25, 35, 30] },
]);

// Helpers de Estilo e SVG
const variantIconMap = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-1 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-1 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-1 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300',
  danger: 'bg-red-500/15 dark:bg-red-500/15 bg-red-100 text-red-300 dark:text-red-300 text-red-700 ring-1 ring-red-500/25 dark:ring-red-500/25 ring-red-300',
  primary: 'bg-violet-500/15 dark:bg-violet-500/15 bg-violet-100 text-violet-300 dark:text-violet-300 text-violet-700 ring-1 ring-violet-500/25 dark:ring-violet-500/25 ring-violet-300',
};

function sparklineIconClasses(variant) {
  return [
    'w-8 h-8 rounded-lg flex items-center justify-center transition-colors duration-300',
    variantIconMap[variant] || variantIconMap.info
  ];
}

function variantToColorClass(variant) {
  const map = { info: 'cyan-500', success: 'emerald-500', warning: 'amber-500', danger: 'red-500', primary: 'violet-500' };
  return map[variant] || 'slate-500';
}

function trendClasses(trend) {
  if (trend > 0) return 'text-emerald-500 dark:text-emerald-400 flex items-center gap-0.5 text-xs font-bold bg-emerald-500/10 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md';
  if (trend < 0) return 'text-rose-500 dark:text-rose-400 flex items-center gap-0.5 text-xs font-bold bg-rose-500/10 dark:bg-rose-500/10 px-1.5 py-0.5 rounded-md';
  return 'text-slate-400 dark:text-slate-500 flex items-center gap-0.5 text-xs font-bold bg-slate-500/10 px-1.5 py-0.5 rounded-md';
}

function sparklineColor(variant) {
  const map = { info: '#06b6d4', success: '#10b981', warning: '#f59e0b', danger: '#ef4444', primary: '#8b5cf6' };
  return map[variant] || '#94a3b8';
}

function sparklinePoints(data) {
  if (!data || data.length === 0) return '';
  const max = Math.max(...data);
  const min = Math.min(...data);
  const range = max - min || 1;
  
  return data.map((val, i) => {
    const x = (i / (data.length - 1)) * 80;
    const y = 32 - ((val - min) / range) * 20 - 6; // Padding bottom 6
    return `${x},${y}`;
  }).join(' ');
}

function sparklineAreaPoints(data) {
  const points = sparklinePoints(data);
  return `0,32 ${points} 80,32`;
}

function sparklineAreaFill(variant) {
  const color = sparklineColor(variant);
  return color; 
}
</script>
<style scoped>
.trend-line {
  stroke-dasharray: 100;
  stroke-dashoffset: 100;
  animation: drawLine 1.5s ease-out forwards;
}
@keyframes drawLine {
  to { stroke-dashoffset: 0; }
}
</style>
