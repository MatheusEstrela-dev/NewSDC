<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <StatCard title="Total de PMDA" :value="statistics.total" variant="info" :icon="DocumentTextIcon" />
    <StatCard title="Em Edição" :value="statistics.emEdicao" variant="warning" :icon="DocumentTextIcon" />
    <StatCard title="Em Análise" :value="statistics.emAnalise" variant="info" :icon="ClockIcon" />
    <StatCard title="Aprovados" :value="statistics.aprovados" variant="success" :icon="CheckIcon" />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({ total: 0, emEdicao: 0, emAnalise: 0, aprovados: 0 }),
  },
});
</script>
