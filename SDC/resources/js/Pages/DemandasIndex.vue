<template>
  <AuthenticatedLayout>
    <Head title="Demandas" />

    <DemandasIndexTemplate
      :can-create="can('demandas.tarefas.create')"
      :can-export="can('demandas.tarefas.export')"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();
</script>


