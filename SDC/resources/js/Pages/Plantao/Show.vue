<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ShowTemplate from '@/Templates/Plantao/ShowTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  plantao: {
    type: Object,
    required: true,
  },
  canRelatorio: {
    type: Boolean,
    default: false,
  },
});
</script>

<template>
  <ShowTemplate
    :plantao="plantao"
    :can-relatorio="canRelatorio"
  />
</template>
