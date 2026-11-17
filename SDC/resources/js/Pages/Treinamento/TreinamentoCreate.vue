<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TreinamentoCreateTemplate from '@/Templates/Treinamento/TreinamentoCreateTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  filterOptions: {
    type: Object,
    default: () => ({ tipos: [], categorias: [] }),
  },
  padroes: {
    type: Object,
    default: () => ({}),
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
  <TreinamentoCreateTemplate :cursos="cursos" :filter-options="filterOptions" :publicos="publicos" :municipios="municipios" :padroes="padroes" />
</template>
