<template>
  <AuthenticatedLayout>
    <Head :title="`Liberação ${liberacao.codigo_legado ?? liberacao.id}`" />

    <LiberacaoAhShowTemplate :liberacao="liberacao" @voltar="voltar" />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LiberacaoAhShowTemplate from '@/Templates/AjudaHumanitaria/LiberacaoAhShowTemplate.vue';

defineProps({
  liberacao: { type: Object, required: true },
});

function voltar() {
  router.get(route('ajuda-humanitaria.liberacoes.index'));
}
</script>
