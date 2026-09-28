<template>
  <StatCardsGrid :colunas="4" :espaco-inferior="false">
    <StatCard title="Abertos" :value="estatisticas.abertas" variant="danger" :icon="InboxIcon" clickable @click="$emit('filtrar', 'aberto')" />
    <StatCard title="Em andamento" :value="estatisticas.em_andamento" variant="warning" :icon="ClockIcon" clickable @click="$emit('filtrar', 'em_andamento')" />
    <StatCard title="Resolvidos hoje" :value="estatisticas.resolvidas_hoje" variant="success" :icon="CheckCircleIcon" />
    <StatCard title="Concluídos" :value="estatisticas.concluidas" variant="info" :icon="CheckBadgeIcon" clickable @click="$emit('filtrar', 'concluido')" />
  </StatCardsGrid>
</template>

<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import InboxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';

defineProps({ estatisticas: { type: Object, required: true } });
defineEmits(['filtrar']);
</script>
