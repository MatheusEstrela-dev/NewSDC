<template>
  <AuthenticatedLayout>
    <Head :title="`Transferência ${transferencia.codigo_legado ?? transferencia.id}`" />

    <TransferenciaAhShowTemplate :transferencia="transferencia" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransferenciaAhShowTemplate from '@/Templates/AjudaHumanitaria/TransferenciaAhShowTemplate.vue';

defineProps({
  transferencia: { type: Object, required: true },
});

</script>
