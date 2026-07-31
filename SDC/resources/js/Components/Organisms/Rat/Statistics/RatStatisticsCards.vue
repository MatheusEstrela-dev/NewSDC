<template>
  <StatCardsGrid>
    <StatCard
      title="Total de RATs"
      :value="statistics.total"
      variant="info"
      :icon="DocumentTextIcon"
    />
    <StatCard
      title="Hoje"
      :value="statistics.hoje"
      variant="success"
      :icon="ClockIcon"
    />
    <StatCard
      title="Este Mês"
      :value="statistics.esteMes"
      variant="warning"
      :icon="CalendarIcon"
    />
    <StatCard
      title="Este Ano"
      :value="statistics.esteAno"
      variant="danger"
      :icon="CalendarIcon"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      hoje: 0,
      esteMes: 0,
      esteAno: 0,
    }),
  },
});
</script>

