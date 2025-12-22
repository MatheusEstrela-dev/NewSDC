<template>
  <AuthenticatedLayout>
    <Head title="Demandas" />

    <DemandasIndexTemplate />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';
</script>


