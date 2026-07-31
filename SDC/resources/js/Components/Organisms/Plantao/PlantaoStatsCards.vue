<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import { computed } from 'vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

// Cards clicaveis sao atalhos de filtro rapido (emitem o status StatusPlantao).
// "Total Turnos" limpa o filtro. "Finalizados Hoje" (metrica com recorte de data) e
// "Equipe Online" (metrica pura) nao mapeiam para um filtro de listagem: ficam como metrica.
const emit = defineEmits(['filter']);

const stats = computed(() => [
  {
    id: 'total',
    title: 'Total Turnos',
    value: props.statistics.total || 0,
    variant: 'info',
    icon: ClockIcon,
    filter: '',
  },
  {
    id: 'ativos',
    title: 'Ativos Agora',
    value: props.statistics.ativos || 0,
    variant: 'success',
    icon: BoltIcon,
    filter: 'ATIVO',
  },
  {
    id: 'finalizados_hoje',
    title: 'Finalizados Hoje',
    value: props.statistics.finalizados_hoje || 0,
    variant: 'warning',
    icon: CheckCircleIcon,
    filter: null,
  },
  {
    id: 'equipe_online',
    title: 'Equipe Online',
    value: props.statistics.equipe_online || 0,
    variant: 'info',
    icon: UsersIcon,
    filter: null,
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
      :clickable="stat.filter !== null"
      @click="stat.filter !== null && emit('filter', stat.filter)"
    />
  </StatCardsGrid>
</template>
