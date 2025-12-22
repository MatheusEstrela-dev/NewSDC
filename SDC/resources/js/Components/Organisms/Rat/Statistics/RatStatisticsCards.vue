<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
  </div>
</template>

<script setup>
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

