<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <StatCard
      title="Total"
      :value="statistics.total ?? 0"
      variant="info"
      :icon="CubeIcon"
      subtitle="Cisternas cadastradas"
    />
    <StatCard
      title="Ativas"
      :value="statistics.ativas ?? 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Em acompanhamento"
    />
    <StatCard
      title="Pendentes"
      :value="statistics.pendentes ?? 0"
      variant="warning"
      :icon="ClockIcon"
      subtitle="Aguardando atualizacao"
    />
    <StatCard
      title="Municipios"
      :value="statistics.municipios ?? 0"
      variant="info"
      :icon="MapIcon"
      subtitle="Com cisternas vinculadas"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({ total: 0, ativas: 0, pendentes: 0, municipios: 0 }),
  },
});
</script>
