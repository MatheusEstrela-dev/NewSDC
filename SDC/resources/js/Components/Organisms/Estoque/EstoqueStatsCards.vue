<template>
  <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
    <button type="button" class="text-left" @click="$emit('filter', 'total')">
      <StatCard title="Produtos" :value="statistics.produtos" variant="info" :icon="ArchiveBoxIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'normal')">
      <StatCard title="Saldo total" :value="statistics.saldo_total" variant="success" :icon="CircleStackIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'normal')">
      <StatCard title="Kits montaveis" :value="statistics.kits_montaveis" variant="info" :icon="SquaresPlusIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'atencao')">
      <StatCard title="Lotes vencendo" :value="statistics.lotes_vencendo" variant="warning" :icon="ClockIcon" />
    </button>
    <button type="button" class="text-left" @click="$emit('filter', 'critico')">
      <StatCard title="Requisicoes" :value="statistics.requisicoes" variant="danger" :icon="ClipboardDocumentCheckIcon" />
    </button>
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import {
  ArchiveBoxIcon,
  CircleStackIcon,
  ClipboardDocumentCheckIcon,
  ClockIcon,
  SquaresPlusIcon,
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
