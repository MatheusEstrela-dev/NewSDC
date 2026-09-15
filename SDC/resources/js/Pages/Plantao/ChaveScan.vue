<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ChaveScanTemplate from '@/Templates/Plantao/ChaveScanTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  minhasReservas: {
    type: Array,
    default: () => [],
  },
  toleranciaMinutos: {
    type: Number,
    default: 30,
  },
  niveis: {
    type: Array,
    default: () => [],
  },
  plantaoAtivoId: {
    type: Number,
    default: null,
  },
});
</script>

<template>
  <ChaveScanTemplate
    :minhas-reservas="props.minhasReservas"
    :tolerancia-minutos="props.toleranciaMinutos"
    :niveis="props.niveis"
    :plantao-ativo-id="props.plantaoAtivoId"
  />
</template>
