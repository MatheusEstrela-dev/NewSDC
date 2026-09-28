<template>
  <StatCardsGrid :colunas="3" :espaco-inferior="false">
    <StatCard title="Categorias ativas" :value="estatisticas.categorias_ativas" variant="info" :icon="ClipboardDocumentListIcon" />
    <StatCard title="Assuntos ativos" :value="estatisticas.assuntos_ativos" variant="success" :icon="CheckCircleIcon" />
    <StatCard title="Assuntos com automação" :value="estatisticas.assuntos_com_automacao" variant="warning" :icon="BoltIcon" />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import { BoltIcon } from '@heroicons/vue/24/outline';

defineProps({
  estatisticas: {
    type: Object,
    required: true,
  },
});
</script>
