<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TreinamentoCreateTemplate from '@/Templates/Treinamento/TreinamentoCreateTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  filterOptions: {
    type: Object,
    default: () => ({ tipos: [], categorias: [] }),
  },
});
</script>

<template>
  <TreinamentoCreateTemplate :filter-options="filterOptions" />
</template>
