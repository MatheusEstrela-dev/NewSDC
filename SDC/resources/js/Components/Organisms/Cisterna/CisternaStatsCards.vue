<template>
  <StatCardsGrid>
    <StatCard
      title="Total"
      :value="statistics.total ?? 0"
      variant="info"
      :icon="CubeIcon"
      subtitle="Cisternas cadastradas"
      clickable
      @click="$emit('filter', '')"
    />
    <StatCard
      title="Ativas"
      :value="statistics.ativas ?? 0"
      variant="success"
      :icon="CheckCircleIcon"
      subtitle="Em acompanhamento"
      clickable
      @click="$emit('filter', 'ativa')"
    />
    <StatCard
      title="Pendentes"
      :value="statistics.pendentes ?? 0"
      variant="warning"
      :icon="ClockIcon"
      subtitle="Aguardando atualizacao"
      clickable
      @click="$emit('filter', 'pendente')"
    />
    <StatCard
      title="Municipios"
      :value="statistics.municipios ?? 0"
      variant="info"
      :icon="MapIcon"
      subtitle="Com cisternas vinculadas"
    />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
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

// Cards com status real sao atalhos de filtro rapido (emitem o status). Municipios e metrica pura.
defineEmits(['filter']);
</script>
