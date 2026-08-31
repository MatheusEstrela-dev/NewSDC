<template>
  <AuthenticatedLayout>
    <Head :title="`Liberação ${liberacao.codigo_legado ?? liberacao.id}`" />

    <LiberacaoAhShowTemplate :liberacao="liberacao" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LiberacaoAhShowTemplate from '@/Templates/AjudaHumanitaria/LiberacaoAhShowTemplate.vue';

defineProps({
  liberacao: { type: Object, required: true },
});

</script>
