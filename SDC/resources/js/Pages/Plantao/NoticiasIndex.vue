<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import NoticiasIndexTemplate from '@/Templates/Plantao/NoticiasIndexTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  noticias: {
    type: Array,
    default: () => [],
  },
  fontes: {
    type: Array,
    default: () => [],
  },
});
</script>

<template>
  <NoticiasIndexTemplate
    :noticias="noticias"
    :fontes="fontes"
  />
</template>
