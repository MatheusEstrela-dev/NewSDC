<template>
  <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    <StatCard title="Produtos" :value="statistics.produtos ?? 0" :icon="ArchiveBoxIcon" variant="info" />
    <StatCard title="Itens em estoque" :value="statistics.saldo_total ?? 0" :icon="CubeIcon" variant="info" />
    <StatCard title="Estoque baixo" :value="statistics.estoque_baixo ?? 0" :icon="ArrowTrendingDownIcon" variant="warning" />
    <StatCard title="Vencem em 30 dias" :value="statistics.lotes_vencendo ?? 0" :icon="ClockIcon" variant="warning"
      clickable @click="$emit('filtrar', { validade: 'vence_30' })" />
    <StatCard title="Em quarentena" :value="statistics.em_quarentena ?? 0" :icon="NoSymbolIcon" variant="danger"
      clickable @click="$emit('filtrar', { status: 'quarentena' })" />
    <StatCard title="Romaneios no mês" :value="statistics.requisicoes ?? 0" :icon="TruckIcon" variant="success" />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import { ArchiveBoxIcon, ArrowTrendingDownIcon, ClockIcon, CubeIcon, NoSymbolIcon, TruckIcon } from '@heroicons/vue/24/outline';

defineProps({
  statistics: { type: Object, default: () => ({}) },
});
defineEmits(['filtrar']);
</script>
