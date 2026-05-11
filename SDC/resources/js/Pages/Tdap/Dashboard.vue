<template>
  <Head title="TDAP — Dashboard" />
  <div class="p-6">
    <TdapPageHeader
      title="TDAP — Transporte e Distribuição de Água Potável"
      description="Gestão de cronogramas, prestadores, caminhões-tanque, vistorias e viagens"
      :icon="TruckIcon"
    />
    <TdapStatisticsCards :kpis="kpis" />
  </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapStatisticsCards from '@/Components/Organisms/Tdap/Statistics/TdapStatisticsCards.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

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
