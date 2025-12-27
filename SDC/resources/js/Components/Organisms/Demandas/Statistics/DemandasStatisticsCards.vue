<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <StatCard
      title="Total de Demandas"
      :value="statistics.total"
      variant="info"
      :icon="CheckBadgeIcon"
    />
    <StatCard
      title="Abertas"
      :value="statistics.abertas"
      variant="warning"
      :icon="ExclamationTriangleIcon"
    />
    <StatCard
      title="Em Andamento"
      :value="statistics.em_andamento"
      variant="success"
      :icon="ClockIcon"
    />
    <StatCard
      title="Concluídas"
      :value="statistics.concluidas"
      variant="danger"
      :icon="CheckCircleIcon"
    />
  </div>
</template>

<script setup>
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
</script>
