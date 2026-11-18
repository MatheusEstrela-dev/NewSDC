<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SalaDeAulaTemplate from '@/Templates/Treinamento/SalaDeAulaTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  curso: { type: Object, required: true },
  estado: { type: Object, required: true },
  escopo: { type: String, default: 'portal' },
  versao_rotulo: { type: String, default: null },
});
</script>

<template>
  <SalaDeAulaTemplate :curso="curso" :estado="estado" :escopo="escopo" :versao-rotulo="versao_rotulo" />
</template>
