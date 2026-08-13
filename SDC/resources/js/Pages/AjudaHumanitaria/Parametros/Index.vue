<template>
  <AuthenticatedLayout>
    <Head title="Parâmetros do Módulo" />

    <ParametroAhTemplate :parametros="parametros" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ParametroAhTemplate from '@/Templates/AjudaHumanitaria/ParametroAhTemplate.vue';

defineProps({
  parametros: { type: Object, required: true },
});
</script>
