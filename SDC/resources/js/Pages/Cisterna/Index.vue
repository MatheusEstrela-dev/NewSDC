<template>
  <CisternaIndexTemplate
    :cisternas="cisternas"
    :statistics="statistics"
    :filters="filters"
    :can-manage="canManage"
  />
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CisternaIndexTemplate from '@/Templates/Cisterna/CisternaIndexTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  cisternas: { type: Object, default: () => ({ data: [] }) },
  statistics: { type: Object, default: () => ({ total: 0, ativas: 0, pendentes: 0, municipios: 0 }) },
  filters: { type: Object, default: () => ({}) },
  canManage: { type: Boolean, default: false },
});
</script>
