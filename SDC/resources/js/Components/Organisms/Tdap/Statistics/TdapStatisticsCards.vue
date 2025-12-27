<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <StatCard
      title="Total de Produtos"
      :value="statistics.total_produtos"
      variant="info"
      :icon="CubeIcon"
    />
    <StatCard
      title="Recebimentos Finalizados"
      :value="statistics.recebimentos_finalizados"
      variant="success"
      :icon="CheckCircleIcon"
    />
    <StatCard
      title="Pendentes"
      :value="statistics.recebimentos_pendentes"
      variant="warning"
      :icon="ClockIcon"
    />
    <StatCard
      title="Movimentações (Mês)"
      :value="statistics.movimentacoes_mes"
      variant="danger"
      :icon="ArrowsRightLeftIcon"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ArrowsRightLeftIcon from '@/Components/Icons/ArrowsRightLeftIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
    default: () => ({
      total_produtos: 0,
      recebimentos_finalizados: 0,
      recebimentos_pendentes: 0,
      movimentacoes_mes: 0,
    }),
  },
});
</script>
