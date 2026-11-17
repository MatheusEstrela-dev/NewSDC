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
  municipios: {
    type: Array,
    default: () => [],
  },
  cursos: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <TreinamentoEditTemplate :cursos="cursos" :treinamento="treinamento" :filter-options="filterOptions" :publicos="publicos" :municipios="municipios" />
</template>
