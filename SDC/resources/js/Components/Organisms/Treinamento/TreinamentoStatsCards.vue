<script setup>
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

const emit = defineEmits(['filter']);

const stats = computed(() => [
  {
    id: 'total',
    title: 'Total',
    value: props.statistics.total || 0,
    variant: 'info',
    icon: BookOpenIcon,
  },
  {
    id: 'planejados',
    title: 'Planejados',
    value: props.statistics.planejados || 0,
    variant: 'warning',
    icon: ClipboardDocumentListIcon,
  },
  {
    id: 'em_andamento',
    title: 'Em Andamento',
    value: props.statistics.em_andamento || 0,
    variant: 'info',
    icon: BoltIcon,
  },
  {
    id: 'concluidos',
    title: 'Concluídos',
    value: props.statistics.concluidos || 0,
    variant: 'success',
    icon: CheckCircleIcon,
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

