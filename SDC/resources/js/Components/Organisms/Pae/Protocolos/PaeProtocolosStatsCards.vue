<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <StatCard
      title="Total de PAE"
      :value="stats.total"
      variant="info"
      :icon="DocumentTextIcon"
      clickable
      @click="$emit('total')"
    />
    <StatCard
      title="Histórico"
      :value="stats.historico"
      variant="success"
      :icon="ClockIcon"
      clickable
      @click="$emit('historico')"
    />
    <StatCard
      title="Vencidos"
      :value="stats.vencidos"
      variant="warning"
      :icon="ExclamationTriangleIcon"
      clickable
      @click="$emit('vencidos')"
    />
    <StatCard
      title="CCPAE"
      :value="stats.ccpae"
      variant="danger"
      :icon="UsersIcon"
      clickable
      @click="$emit('ccpae')"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import UsersIcon from '@/Components/Icons/UsersIcon.vue';

defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      total: 0,
      historico: 0,
      vencidos: 0,
      ccpae: 0,
    }),
  },
});

defineEmits(['total', 'historico', 'vencidos', 'ccpae']);
</script>
