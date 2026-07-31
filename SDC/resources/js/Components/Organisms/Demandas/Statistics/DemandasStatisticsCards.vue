<template>
  <StatCardsGrid>
    <StatCard
      title="Total de Demandas"
      :value="statistics.total"
      variant="info"
      :icon="CheckBadgeIcon"
      clickable
      @click="$emit('filter', '')"
    />
    <StatCard
      title="Abertas"
      :value="statistics.abertas"
      variant="warning"
      :icon="ExclamationTriangleIcon"
      clickable
      @click="$emit('filter', 'aberta')"
    />
    <StatCard
      title="Em Andamento"
      :value="statistics.em_andamento"
      variant="success"
      :icon="ClockIcon"
      clickable
      @click="$emit('filter', 'em_andamento')"
    />
    <StatCard
      title="Concluídas"
      :value="statistics.concluidas"
      variant="danger"
      :icon="CheckCircleIcon"
      clickable
      @click="$emit('filter', 'concluida')"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      abertas: 0,
      em_andamento: 0,
      concluidas: 0,
    }),
  },
});

// Cada card, alem de exibir o numero, e um atalho de filtro rapido por status.
// Total limpa o filtro (emite '').
defineEmits(['filter']);
</script>
