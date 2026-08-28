<script setup>
/**
 * Cobertura do mes, no mesmo padrao do PlantaoStatsCards.
 *
 * "Dias descobertos" e a metrica que justifica a tela existir: sao os dias sem
 * nenhuma vaga preenchida, ou seja, os buracos que alguem precisa fechar antes
 * de publicar. Por isso ele muda de cor -- verde quando o mes esta fechado,
 * vermelho quando ainda ha furo.
 *
 * "Minhas vagas" e clicavel e vira filtro rapido, como os cards do Plantao
 * Diario. Os outros sao metrica pura: nao ha filtro correspondente.
 */
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import UserIcon from '@/Components/Icons/UserIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import { computed } from 'vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['filtrar-meus']);

const stats = computed(() => [
  {
    id: 'vagas',
    title: 'Vagas no mes',
    value: props.statistics.vagas || 0,
    variant: 'info',
    icon: CalendarIcon,
    clickable: false,
  },
  {
    id: 'minhas',
    title: 'Meus plantoes',
    value: props.statistics.minhas || 0,
    variant: 'info',
    icon: UserIcon,
    clickable: true,
  },
  {
    id: 'assumidas',
    title: 'Turnos assumidos',
    value: props.statistics.assumidas || 0,
    variant: 'success',
    icon: CheckCircleIcon,
    clickable: false,
  },
  {
    id: 'descobertos',
    title: 'Dias descobertos',
    value: props.statistics.dias_descobertos || 0,
    variant: (props.statistics.dias_descobertos || 0) > 0 ? 'danger' : 'success',
    icon: ExclamationTriangleIcon,
    clickable: false,
  },
]);
</script>

<template>
  <StatCardsGrid>
    <StatCard
      v-for="stat in stats"
      :key="stat.id"
      :title="stat.title"
      :value="stat.value"
      :variant="stat.variant"
      :icon="stat.icon"
      :clickable="stat.clickable"
      @click="stat.clickable && emit('filtrar-meus')"
    />
  </StatCardsGrid>
</template>
