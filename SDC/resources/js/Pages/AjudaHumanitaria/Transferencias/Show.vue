<template>
  <AuthenticatedLayout>
    <Head :title="`Transferência ${transferencia.codigo_legado ?? transferencia.id}`" />

    <TransferenciaAhShowTemplate :transferencia="transferencia" @voltar="voltar" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TransferenciaAhShowTemplate from '@/Templates/AjudaHumanitaria/TransferenciaAhShowTemplate.vue';

defineProps({
  transferencia: { type: Object, required: true },
});

function voltar() {
  router.get(route('ajuda-humanitaria.transferencias.index'));
}
</script>
