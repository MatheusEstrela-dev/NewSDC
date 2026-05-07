<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <StatCard
      title="Total de Orgaos"
      :value="statistics.total || 0"
      variant="info"
      :icon="BuildingOfficeIcon"
    />

    <StatCard
      title="COMPDECs"
      :value="statistics.por_tipo?.compdec || 0"
      variant="info"
      :icon="BuildingIcon"
    />

    <StatCard
      title="REDECs"
      :value="statistics.por_tipo?.redec || 0"
      variant="warning"
      :icon="BuildingOfficeIcon"
    />

    <StatCard
      title="Ativos"
      :value="statistics.ativos || 0"
      variant="success"
      :icon="CheckCircleIcon"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      ativos: 0,
      por_tipo: {
        compdec: 0,
        redec: 0,
        cedec: 0,
      },
    }),
  },
});
</script>
