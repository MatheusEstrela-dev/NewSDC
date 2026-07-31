<script setup>
import StatCardsGrid from '@/Components/Molecules/Statistics/StatCardsGrid.vue';
import { computed } from 'vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
  },
});

// Cada card e um atalho de filtro rapido: emite o status (StatusTreinamento) a ser aplicado.
// Card "Total" limpa o filtro (status vazio). Sem anel de marcacao no card ativo.
const emit = defineEmits(['filter']);

const stats = computed(() => [
  {
    id: 'total',
    title: 'Total',
    value: props.statistics.total || 0,
    variant: 'info',
    icon: BookOpenIcon,
    status: '',
  },
  {
    id: 'planejados',
    title: 'Planejados',
    value: props.statistics.planejados || 0,
    variant: 'warning',
    icon: ClipboardDocumentListIcon,
    status: 'PLANEJADO',
  },
  {
    id: 'em_andamento',
    title: 'Em Andamento',
    value: props.statistics.em_andamento || 0,
    variant: 'info',
    icon: BoltIcon,
    status: 'EM_ANDAMENTO',
  },
  {
    id: 'concluidos',
    title: 'Concluídos',
    value: props.statistics.concluidos || 0,
    variant: 'success',
    icon: CheckCircleIcon,
    status: 'CONCLUIDO',
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
      clickable
      @click="emit('filter', stat.status)"
    />
  </StatCardsGrid>
</template>

