<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SalaDeAulaTemplate from '@/Templates/Treinamento/SalaDeAulaTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  curso: { type: Object, required: true },
  estado: { type: Object, required: true },
  escopo: { type: String, default: 'portal' },
});
</script>

<template>
  <SalaDeAulaTemplate :curso="curso" :estado="estado" :escopo="escopo" />
</template>
