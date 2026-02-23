<template>
  <div
    :class="[
      'event-stat-card',
      clickable && 'cursor-pointer hover:shadow-lg',
      loading && 'animate-pulse'
    ]"
    @click="clickable && $emit('click')"
  >
    <!-- Skeleton Loading -->
    <template v-if="loading">
      <div class="h-4 bg-slate-200 rounded w-2/3 mb-3"></div>
      <div class="h-12 bg-slate-200 rounded w-1/2 mb-4"></div>
      <div class="flex justify-center gap-6">
        <div class="h-6 bg-slate-200 rounded w-12"></div>
        <div class="h-6 bg-slate-200 rounded w-12"></div>
      </div>
    </template>

    <!-- Content -->
    <template v-else>
      <!-- Title -->
      <div class="card-title">
        {{ title }}
      </div>

      <!-- Main Value -->
      <div class="card-value" :class="valueColorClass">
        {{ formattedValue }}
      </div>

      <!-- Breakdown ECP / SE -->
      <div v-if="showBreakdown" class="card-breakdown">
        <div class="breakdown-item">
          <span class="breakdown-label">ECP</span>
          <span class="breakdown-value" :class="ecpValueClass">
            {{ formatNumber(ecp) }}
          </span>
        </div>
        <div class="breakdown-item">
          <span class="breakdown-label">SE</span>
          <span class="breakdown-value" :class="seValueClass">
            {{ formatNumber(se) }}
          </span>
        </div>
      </div>

      <!-- Trend Indicator (opcional) -->
      <div v-if="trend !== null" class="card-trend" :class="trendClass">
        <svg v-if="trend > 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
        </svg>
        <svg v-else-if="trend < 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
        <span class="text-xs font-medium">{{ Math.abs(trend) }}%</span>
      </div>

      <!-- Real-time indicator -->
      <div v-if="realTime" class="real-time-indicator">
        <span class="pulse-dot"></span>
        <span class="text-xs text-slate-500">Tempo real</span>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  value: {
    type: [Number, String],
    default: 0,
  },
  ecp: {
    type: Number,
    default: 0,
  },
  se: {
    type: Number,
    default: 0,
  },
  showBreakdown: {
    type: Boolean,
    default: true,
  },
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'success', 'warning', 'danger', 'info'].includes(v),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  clickable: {
    type: Boolean,
    default: true,
  },
  trend: {
    type: Number,
    default: null,
  },
  realTime: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['click']);

function formatNumber(num) {
  if (num === null || num === undefined) return '--';
  return num.toLocaleString('pt-BR');
}

const formattedValue = computed(() => {
  if (props.loading) return '--';
  return formatNumber(props.value);
});

const valueColorClass = computed(() => {
  const colors = {
    primary: 'text-blue-600',
    success: 'text-emerald-600',
    warning: 'text-amber-600',
    danger: 'text-red-600',
    info: 'text-cyan-600',
  };
  return colors[props.variant] || colors.primary;
});

const ecpValueClass = computed(() => {
  return props.ecp > 0 ? 'text-blue-600' : 'text-slate-600';
});

const seValueClass = computed(() => {
  return props.se > 0 ? 'text-blue-600' : 'text-slate-600';
});

const trendClass = computed(() => {
  if (props.trend > 0) return 'trend-up';
  if (props.trend < 0) return 'trend-down';
  return '';
});
</script>

<style scoped>
.event-stat-card {
  @apply relative bg-white rounded-2xl p-5 text-center transition-all duration-200;
  @apply border border-slate-100 shadow-sm;
  min-height: 140px;
}

.event-stat-card:hover {
  @apply shadow-md;
}

.card-title {
  @apply text-sm font-semibold text-slate-700 mb-2;
  @apply leading-tight;
}

.card-value {
  @apply text-4xl md:text-5xl font-bold mb-3;
  @apply transition-all duration-300;
}

.card-breakdown {
  @apply flex justify-center gap-6 pt-2 border-t border-slate-100;
}

.breakdown-item {
  @apply flex flex-col items-center;
}

.breakdown-label {
  @apply text-xs font-medium text-slate-400 uppercase tracking-wide;
}

.breakdown-value {
  @apply text-lg font-bold;
}

.card-trend {
  @apply absolute top-3 right-3 flex items-center gap-1 px-2 py-1 rounded-full text-xs;
}

.card-trend.trend-up {
  @apply bg-emerald-50 text-emerald-600;
}

.card-trend.trend-down {
  @apply bg-red-50 text-red-600;
}

.real-time-indicator {
  @apply absolute bottom-2 right-2 flex items-center gap-1;
}

.pulse-dot {
  @apply w-2 h-2 bg-emerald-500 rounded-full;
  animation: pulse-animation 2s infinite;
}

@keyframes pulse-animation {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.5;
    transform: scale(0.8);
  }
}

/* Dark mode support */
:deep(.dark) .event-stat-card {
  @apply bg-slate-800 border-slate-700;
}

:deep(.dark) .card-title {
  @apply text-slate-300;
}

:deep(.dark) .card-breakdown {
  @apply border-slate-700;
}

:deep(.dark) .breakdown-label {
  @apply text-slate-500;
}
</style>
