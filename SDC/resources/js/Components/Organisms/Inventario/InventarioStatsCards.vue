<template>
  <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
    <button type="button" class="text-left" @click="$emit('filter', 'total')">
      <StatCard title="Total" :value="statistics.total" variant="info" :icon="ArchiveBoxIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'disponivel')">
      <StatCard title="Disponiveis" :value="statistics.disponiveis" variant="success" :icon="CheckCircleIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'emprestado')">
      <StatCard title="Emprestados" :value="statistics.emprestados" variant="info" :icon="ArrowPathRoundedSquareIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'manutencao')">
      <StatCard title="Manutencao" :value="statistics.manutencao" variant="warning" :icon="WrenchScrewdriverIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'baixado')">
      <StatCard title="Baixados" :value="statistics.baixados" variant="danger" :icon="ArchiveBoxXMarkIcon" />
    </button>
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
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

defineEmits(['filter']);
</script>
