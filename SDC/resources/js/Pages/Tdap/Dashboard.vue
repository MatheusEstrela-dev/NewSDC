<template>
  <AuthenticatedLayout>
    <Head title="TDAP - Dashboard" />
    <TdapDashboardTemplate :statistics="statistics" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapDashboardTemplate from '@/Templates/Tdap/TdapDashboardTemplate.vue';

const props = defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total_produtos: 2,
      recebimentos_finalizados: 0,
      recebimentos_pendentes: 0,
      movimentacoes_mes: 0,
    }),
  },
});
</script>
