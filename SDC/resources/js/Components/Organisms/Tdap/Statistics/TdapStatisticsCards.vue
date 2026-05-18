<template>
  <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3 2xl:grid-cols-5 mb-6">
    <StatCard
      title="Cronogramas Ativos"
      :value="kpis.cronogramas_ativos"
      variant="info"
      :icon="CalendarIcon"
    />
    <StatCard
      title="Cronogramas Encerrados"
      :value="kpis.cronogramas_encerrados"
      variant="success"
      :icon="CheckCircleIcon"
    />
    <StatCard
      title="M³ Entregues (Mês)"
      :value="kpis.m3_entregues_mes"
      variant="info"
      :icon="TruckIcon"
    />
    <StatCard
      title="Prestadores Ativos"
      :value="kpis.prestadores_ativos"
      variant="success"
      :icon="BuildingIcon"
    />
    <StatCard
      class="col-span-2 lg:col-span-1"
      title="Viagens p/ Validar"
      :value="kpis.viagens_pendentes_validar"
      variant="warning"
      :icon="ClockIcon"
    />
  </div>
</template>

<script setup>
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineProps({
  kpis: {
    type: Object,
    required: true,
    default: () => ({
      cronogramas_ativos: 0,
      cronogramas_encerrados: 0,
      m3_entregues_mes: 0,
      prestadores_ativos: 0,
      viagens_pendentes_validar: 0,
    }),
  },
});
</script>
