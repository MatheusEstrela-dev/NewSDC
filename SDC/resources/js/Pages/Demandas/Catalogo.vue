<template>
  <Head title="Catálogo de demandas" />
  <CatalogoDemandasTemplate :categorias="categorias" :assuntos="assuntos" :estatisticas="estatisticas" />
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import CatalogoDemandasTemplate from '@/Templates/Demandas/CatalogoDemandasTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  categorias: { type: Array, required: true },
  assuntos: { type: Array, required: true },
  estatisticas: { type: Object, required: true },
});
</script>
