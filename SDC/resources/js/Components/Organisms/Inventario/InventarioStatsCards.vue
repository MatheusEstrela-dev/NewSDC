<template>
  <StatCardsGrid>
    <StatCard title="Total" :value="statistics.total" variant="info" :icon="ArchiveBoxIcon" clickable @click="$emit('filter', '')" />
    <StatCard title="Disponiveis" :value="statistics.disponiveis" variant="success" :icon="CheckCircleIcon" clickable @click="$emit('filter', 'disponivel')" />
    <StatCard title="Emprestados" :value="statistics.emprestados" variant="info" :icon="ArrowPathRoundedSquareIcon" clickable @click="$emit('filter', 'emprestado')" />
    <StatCard title="Manutencao" :value="statistics.manutencao" variant="warning" :icon="WrenchScrewdriverIcon" clickable @click="$emit('filter', 'manutencao')" />
    <StatCard title="Baixados" :value="statistics.baixados" variant="danger" :icon="ArchiveBoxXMarkIcon" clickable @click="$emit('filter', 'baixado')" />
  </StatCardsGrid>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import {
  ArchiveBoxIcon,
  ArchiveBoxXMarkIcon,
  ArrowPathRoundedSquareIcon,
  CheckCircleIcon,
  WrenchScrewdriverIcon,
} from '@heroicons/vue/24/outline';

defineProps({
  statistics: {
    type: Object,
    required: true,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

// Cada card e um atalho de filtro rapido por situacao; '' = Total (limpa o filtro).
defineEmits(['filter']);
</script>
