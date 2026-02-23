<script setup>
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

const emit = defineEmits(['filter']);

const stats = computed(() => [
  {
    id: 'total',
    title: 'Total Turnos',
    value: props.statistics.total || 0,
    variant: 'info',
    icon: ClockIcon,
  },
  {
    id: 'ativos',
    title: 'Ativos Agora',
    value: props.statistics.ativos || 0,
    variant: 'success',
    icon: BoltIcon,
  },
  {
    id: 'finalizados_hoje',
    title: 'Finalizados Hoje',
    value: props.statistics.finalizados_hoje || 0,
    variant: 'warning',
    icon: CheckCircleIcon,
  },
  {
    id: 'equipe_online',
    title: 'Equipe Online',
    value: props.statistics.equipe_online || 0,
    variant: 'info',
    icon: UsersIcon,
  },
]);
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div
      v-for="stat in stats"
      :key="stat.id"
      @click="emit('filter', stat.id)"
      class="cursor-pointer"
    >
      <StatCard
        :title="stat.title"
        :value="stat.value"
        :variant="stat.variant"
        :icon="stat.icon"
      />
    </div>
  </div>
</template>
