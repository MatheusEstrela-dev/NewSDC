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
// "Total Turnos" limpa o filtro. "Finalizados Hoje" (metrica com recorte de data)
// nao mapeia para um filtro de listagem: fica como metrica pura.
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
    id: 'pendentes_aceite',
    title: 'Pendentes de aceite',
    value: props.statistics.pendentes_aceite || 0,
    variant: 'warning',
    icon: UsersIcon,
    filter: 'PENDENTE_ACEITE',
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
