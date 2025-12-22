<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <StatCard
      title="Total de PAE"
      :value="stats.total"
      variant="info"
      :icon="DocumentTextIcon"
    />
    <StatCard
      title="Histórico"
      :value="stats.historico"
      variant="success"
      :icon="ClockIcon"
    />
    <StatCard
      title="Vencidos"
      :value="stats.vencidos"
      variant="warning"
      :icon="ExclamationTriangleIcon"
    />
    <StatCard
      title="CCPAE"
      :value="stats.ccpae"
      variant="danger"
      :icon="UsersIcon"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      historico: 0,
      vencidos: 0,
      ccpae: 0,
    }),
  },
});
</script>


