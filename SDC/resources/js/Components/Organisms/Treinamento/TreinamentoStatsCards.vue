<script setup>
import { computed } from 'vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

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
    label: 'Total',
    value: props.statistics.total || 0,
    color: 'bg-blue-500',
    icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
  },
  {
    id: 'planejados',
    label: 'Planejados',
    value: props.statistics.planejados || 0,
    color: 'bg-yellow-500',
    icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
  },
  {
    id: 'em_andamento',
    label: 'Em Andamento',
    value: props.statistics.em_andamento || 0,
    color: 'bg-blue-600',
    icon: 'M13 10V3L4 14h7v7l9-11h-7z',
  },
  {
    id: 'concluidos',
    label: 'Concluídos',
    value: props.statistics.concluidos || 0,
    color: 'bg-green-500',
    icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
]);
</script>

<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <div
      v-for="stat in stats"
      :key="stat.id"
      @click="emit('filter', stat.id)"
      class="stat-card bg-white dark:bg-slate-800 rounded-lg p-4 shadow-sm hover:shadow-md transition-all cursor-pointer"
    >
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <Text size="sm" color="muted" class="mb-1">
            {{ stat.label }}
          </Text>
          <div class="text-2xl font-bold text-slate-800 dark:text-slate-100">
            {{ stat.value }}
          </div>
        </div>
        <div :class="[stat.color, 'p-3 rounded-lg']">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="stat.icon" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  @apply transition-all duration-200;
}

.stat-card:hover {
  @apply transform scale-105;
}
</style>
