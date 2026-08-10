<template>
  <StatCardsGrid>
    <StatCard
      title="Total"
      :value="statistics.total || 0"
      variant="info"
      :icon="ArrowsRightLeftIcon"
      subtitle="Transferências no período"
      clickable
      @click="$emit('filter', { status: '' })"
    />
    <StatCard
      title="Em trânsito"
      :value="statistics.em_transito || 0"
      variant="warning"
      :icon="TruckIcon"
      subtitle="Saíram e não chegaram"
      clickable
      @click="$emit('filter', { status: 0 })"
    />
    <StatCard
      title="Concluídas"
      :value="statistics.concluidas || 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Chegaram ao destino"
      clickable
      @click="$emit('filter', { status: 1 })"
    />
    <StatCard
      title="Canceladas"
      :value="statistics.canceladas || 0"
      variant="danger"
      :icon="XMarkIcon"
      subtitle="Correção de lançamento"
      clickable
      @click="$emit('filter', { status: 2 })"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';

defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

defineEmits(['filter']);
</script>
