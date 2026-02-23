<template>
  <div class="stats-card" :class="variant">
    <div class="stats-icon-container">
      <div class="stats-icon-bg">
        <component :is="icon" class="stats-icon" />
      </div>
    </div>
    <div class="stats-content">
      <div class="stats-label">{{ label }}</div>
      <div class="stats-value">{{ formattedValue }}</div>
      <div v-if="change" class="stats-change" :class="changeClass">
        <svg v-if="change > 0" class="change-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
        </svg>
        <svg v-else class="change-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6" />
        </svg>
        <span>{{ Math.abs(change) }}%</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: [Object, Function],
    required: true
  },
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'primary', 'success', 'warning', 'danger'].includes(value)
  },
  change: {
    type: Number,
    default: null
  }
});

const formattedValue = computed(() => {
  if (typeof props.value === 'number') {
    return props.value.toLocaleString('pt-BR');
  }
  return props.value;
});

const changeClass = computed(() => {
  if (!props.change) return '';
  return props.change > 0 ? 'positive' : 'negative';
});
</script>

<style scoped>
.stats-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #1e293b;
  border: 1px solid #334155;
  border-radius: 12px;
  transition: all 0.2s;
  min-height: 100px;
}

/* Tablet e Desktop */
@media (min-width: 640px) {
  .stats-card {
    gap: 1.25rem;
    padding: 1.25rem;
    min-height: 110px;
  }
}

@media (min-width: 1024px) {
  .stats-card {
    padding: 1.5rem;
    min-height: 120px;
  }
}

.stats-card:hover {
  border-color: #475569;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* Mobile: Desabilitar hover effect em touch devices */
@media (hover: none) and (pointer: coarse) {
  .stats-card:hover {
    transform: none;
  }

  .stats-card:active {
    transform: scale(0.98);
  }
}

.stats-card.primary {
  border-left: 4px solid #3b82f6;
}

.stats-card.success {
  border-left: 4px solid #10b981;
}

.stats-card.warning {
  border-left: 4px solid #f59e0b;
}

.stats-card.danger {
  border-left: 4px solid #ef4444;
}

.stats-icon-container {
  flex-shrink: 0;
}

.stats-icon-bg {
  width: 44px;
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
}

@media (min-width: 640px) {
  .stats-icon-bg {
    width: 52px;
    height: 52px;
    border-radius: 12px;
  }
}

@media (min-width: 1024px) {
  .stats-icon-bg {
    width: 56px;
    height: 56px;
  }
}

.stats-card.primary .stats-icon-bg {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%);
}

.stats-card.success .stats-icon-bg {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
}

.stats-card.warning .stats-icon-bg {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.05) 100%);
}

.stats-card.danger .stats-icon-bg {
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.05) 100%);
}

.stats-icon {
  width: 22px;
  height: 22px;
  color: #60a5fa;
}

@media (min-width: 640px) {
  .stats-icon {
    width: 26px;
    height: 26px;
  }
}

@media (min-width: 1024px) {
  .stats-icon {
    width: 28px;
    height: 28px;
  }
}

.stats-card.success .stats-icon {
  color: #34d399;
}

.stats-card.warning .stats-icon {
  color: #fbbf24;
}

.stats-card.danger .stats-icon {
  color: #f87171;
}

.stats-content {
  flex: 1;
  min-width: 0;
}

.stats-label {
  color: #94a3b8;
  font-size: 0.75rem;
  font-weight: 500;
  margin-bottom: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (min-width: 640px) {
  .stats-label {
    font-size: 0.8125rem;
  }
}

@media (min-width: 1024px) {
  .stats-label {
    font-size: 0.875rem;
  }
}

.stats-value {
  color: #f1f5f9;
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1.2;
  margin-bottom: 0.25rem;
}

@media (min-width: 640px) {
  .stats-value {
    font-size: 1.75rem;
  }
}

@media (min-width: 1024px) {
  .stats-value {
    font-size: 2rem;
  }
}

.stats-change {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-size: 0.75rem;
  font-weight: 600;
}

@media (min-width: 640px) {
  .stats-change {
    font-size: 0.8125rem;
  }
}

.stats-change.positive {
  color: #34d399;
}

.stats-change.negative {
  color: #f87171;
}

.change-icon {
  width: 14px;
  height: 14px;
}
</style>
