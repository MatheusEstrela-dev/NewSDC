<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TreinamentoEditTemplate from '@/Templates/Treinamento/TreinamentoEditTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  treinamento: {
    type: Object,
    required: true,
  },
  filterOptions: {
    type: Object,
    default: () => ({ tipos: [], categorias: [] }),
  },
  publicos: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <TreinamentoEditTemplate :treinamento="treinamento" :filter-options="filterOptions" :publicos="publicos" />
</template>
