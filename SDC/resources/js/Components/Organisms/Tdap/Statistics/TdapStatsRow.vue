<template>
  <div :class="gridClass">
    <StatCard
      v-for="(card, idx) in cards"
      :key="card.key ?? card.title ?? idx"
      :title="card.title ?? card.label"
      :value="formatValue(card)"
      :icon="card.icon"
      :variant="resolveVariant(card.variant ?? card.tone)"
      :subtitle="card.subtitle"
      :format-number="false"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

const props = defineProps({
  cards: {
    type: Array,
    required: true,
  },
  columns: {
    type: Number,
    default: null,
  },
});

const COLUMNS_CLASS = {
  2: 'lg:grid-cols-2',
  3: 'lg:grid-cols-3',
  4: 'lg:grid-cols-4',
  5: 'lg:grid-cols-5',
  6: 'lg:grid-cols-6',
};

const TONE_TO_VARIANT = {
  default: 'info',
  info: 'info',
  success: 'success',
  warning: 'warning',
  danger: 'danger',
  muted: 'info',
};

const gridClass = computed(() => {
  const cols = props.columns ?? props.cards.length;
  const mdClass = COLUMNS_CLASS[cols] ?? 'lg:grid-cols-4';
  return ['grid grid-cols-2 md:grid-cols-2 gap-3 sm:gap-4', mdClass];
});

function resolveVariant(value) {
  if (!value) return 'info';
  return TONE_TO_VARIANT[value] ?? value;
}

function formatValue(card) {
  const v = card.value;
  if (v === null || v === undefined || v === '') return '0';
  if (card.format === 'currency' || card.format === 'decimal') {
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  if (card.format === 'integer') {
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
  }
  if (typeof v === 'number') {
    return v.toLocaleString('pt-BR');
  }
  return v;
}
</script>
