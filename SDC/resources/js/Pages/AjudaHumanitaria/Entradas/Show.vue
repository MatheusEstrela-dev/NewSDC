<template>
  <AuthenticatedLayout>
    <Head :title="titulo" />

    <EntradaAhShowTemplate :entrada="entrada" />
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EntradaAhShowTemplate from '@/Templates/AjudaHumanitaria/EntradaAhShowTemplate.vue';

const props = defineProps({
  entrada: { type: Object, required: true },
});

const titulo = computed(() => 'Entrada ' + (props.entrada.codigo_legado ?? props.entrada.id));
</script>
