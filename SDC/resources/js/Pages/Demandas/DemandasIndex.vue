<template>
  <AuthenticatedLayout>
    <Head title="Demandas" />
    <DemandasIndexTemplate :statistics="statistics" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';

defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      abertas: 0,
      em_andamento: 0,
      concluidas: 0,
    }),
  },
});
</script>
