<template>
  <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <StatCard
      title="Total de Orgaos"
      :value="statistics.total || 0"
      variant="info"
      :icon="BuildingOfficeIcon"
      clickable
      @click="$emit('filter', {})"
    />

    <StatCard
      title="COMPDECs"
      :value="statistics.por_tipo?.compdec || 0"
      variant="info"
      :icon="BuildingIcon"
      clickable
      @click="$emit('filter', { tipo: 'compdec' })"
    />

    <StatCard
      title="REDECs"
      :value="statistics.por_tipo?.redec || 0"
      variant="warning"
      :icon="BuildingOfficeIcon"
      clickable
      @click="$emit('filter', { tipo: 'redec' })"
    />

    <StatCard
      title="Ativos"
      :value="statistics.ativos || 0"
      variant="success"
      :icon="CheckCircleIcon"
      clickable
      @click="$emit('filter', { status: 'ativo' })"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      ativos: 0,
      por_tipo: {
        compdec: 0,
        redec: 0,
        cedec: 0,
      },
    }),
  },
});

// Cards como atalho de filtro rapido: emitem o par {tipo} ou {status}; Total emite {} (limpa esses filtros).
defineEmits(['filter']);
</script>
