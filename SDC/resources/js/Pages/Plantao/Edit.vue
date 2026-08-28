<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditTemplate from '@/Templates/Plantao/EditTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  plantao: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <EditTemplate :plantao="plantao" />
</template>
