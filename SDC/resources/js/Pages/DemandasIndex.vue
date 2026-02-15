<template>
    <div>
        <Head title="Demandas" />
        <DemandasIndexTemplate
          :can-create="can('demandas.tarefas.create')"
          :can-export="can('demandas.tarefas.export')"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();
</script>


