<template>
  <Head :title="`Demanda ${demanda.protocolo}`" />
  <DemandasShowTemplate v-bind="$props" />
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasShowTemplate from '@/Templates/Demandas/DemandasShowTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  demanda: { type: Object, required: true },
  campos: { type: Array, default: () => [] },
  comentarios: { type: Array, default: () => [] },
  historico: { type: Array, default: () => [] },
  anexos: { type: Array, default: () => [] },
  assuntos: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  automacao: { type: Object, required: true },
  pode: { type: Object, required: true },
});
</script>
