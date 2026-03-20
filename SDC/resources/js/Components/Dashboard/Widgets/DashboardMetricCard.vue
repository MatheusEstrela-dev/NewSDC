<template>
  <div :class="['relative overflow-hidden group rounded-xl shadow-lg border bg-white dark:bg-slate-900', metricCardClasses(variant)]">
    <div class="p-4 flex items-start justify-between gap-2 sm:gap-4 relative z-10">
      <div class="min-w-0 flex-1">
        <p class="text-xs sm:text-sm font-medium text-slate-400 dark:text-slate-400 mb-0.5 sm:mb-1 leading-tight">
          {{ title }}
        </p>
        <p class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-100 dark:text-slate-100 mb-0">
          {{ value.toLocaleString('pt-BR') }}
        </p>
        <div class="flex items-center gap-2 mt-1.5">
          <span :class="trendClasses(trend)">
            <svg v-if="trend > 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
            </svg>
            <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
            </svg>
            {{ Math.abs(trend) }}%
          </span>
          <span class="text-[11px] text-slate-500 hidden sm:inline">{{ subtitle }}</span>
        </div>
      </div>
      <div :class="metricIconClasses(variant)">
        <component :is="icon" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: Number,
  trend: Number,
  subtitle: String,
  variant: {
    type: String,
    default: 'info'
  },
  icon: Object
});

const variantBorderMap = {
  info: 'border-cyan-500/25 dark:border-cyan-500/25 border-cyan-200',
  success: 'border-emerald-500/25 dark:border-emerald-500/25 border-emerald-200',
  warning: 'border-amber-500/25 dark:border-amber-500/25 border-amber-200',
  danger: 'border-red-500/25 dark:border-red-500/25 border-red-200',
  primary: 'border-violet-500/25 dark:border-violet-500/25 border-violet-200',
};

const variantIconMap = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-1 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-1 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-1 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300',
  danger: 'bg-red-500/15 dark:bg-red-500/15 bg-red-100 text-red-300 dark:text-red-300 text-red-700 ring-1 ring-red-500/25 dark:ring-red-500/25 ring-red-300',
  primary: 'bg-violet-500/15 dark:bg-violet-500/15 bg-violet-100 text-violet-300 dark:text-violet-300 text-violet-700 ring-1 ring-violet-500/25 dark:ring-violet-500/25 ring-violet-300',
};

function metricCardClasses(variant) {
  return [
    'transition-colors duration-300',
    variantBorderMap[variant] || variantBorderMap.info
  ];
}

function metricIconClasses(variant) {
  return [
    'p-2 sm:p-2.5 rounded-lg sm:rounded-xl flex items-center justify-center transition-colors duration-300 group-hover:scale-110 group-hover:rotate-3 shadow-lg',
    variantIconMap[variant] || variantIconMap.info
  ];
}

function trendClasses(trend) {
  if (trend > 0) return 'text-emerald-500 dark:text-emerald-400 flex items-center gap-0.5 text-xs font-bold bg-emerald-500/10 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md';
  if (trend < 0) return 'text-rose-500 dark:text-rose-400 flex items-center gap-0.5 text-xs font-bold bg-rose-500/10 dark:bg-rose-500/10 px-1.5 py-0.5 rounded-md';
  return 'text-slate-400 dark:text-slate-500 flex items-center gap-0.5 text-xs font-bold bg-slate-500/10 px-1.5 py-0.5 rounded-md';
}
</script>
