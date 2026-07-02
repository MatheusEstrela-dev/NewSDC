<template>
  <div class="mb-6 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
    <StatCard title="Produtos" :value="statistics.produtos" variant="info" :icon="ArchiveBoxIcon" />
    <StatCard title="Saldo total" :value="statistics.saldo_total" variant="success" :icon="CircleStackIcon" />
    <StatCard title="Kits montaveis" :value="statistics.kits_montaveis" variant="info" :icon="SquaresPlusIcon" />
    <StatCard title="Lotes vencendo" :value="statistics.lotes_vencendo" variant="warning" :icon="ClockIcon" />
    <StatCard title="Requisicoes" :value="statistics.requisicoes" variant="danger" :icon="ClipboardDocumentCheckIcon" />
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

// Metricas puras (somatorios/contagens) sem correspondencia com o filtro de status; nao sao clicaveis.
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
</script>
