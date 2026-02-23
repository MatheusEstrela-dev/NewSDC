<template>
    <div>
        <Head title="Demandas" />
        <DemandasIndexTemplate
          :statistics="statistics"
          :can-create="can('demandas.chamados.create')"
          :can-edit="can('demandas.chamados.edit')"
          :can-delete="can('demandas.chamados.delete')"
          :can-export="can('demandas.chamados.export')"
          :can-manage="can('demandas.chamados.manage')"
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

defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      abertas: 0,
      em_andamento: 0,
      concluidas: 0,
    }),
  },
});
</script>
