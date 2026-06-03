<template>
    <div>
        <Head title="Protocolos PAE" />

        <PaeProtocolosIndexTemplate
          :use-mock="false"
          :loading="false"
          :protocolos="protocolos"
          :statistics="statistics"
          :filters="filters"
          :status-options="statusOptions"
          :analistas="analistas"
          :empreendedores="empreendedores"
          :can-create="can('pae.protocolos.create')"
          :can-edit="can('pae.protocolos.edit')"
          :can-delete="can('pae.protocolos.delete')"
          :can-export="can('pae.protocolos.export')"
          :can-atribuir="can('pae.protocolos.atribuir')"
          :can-check="props.canCheck"
          :can-pdf="props.canPdf"
          :pode-ver-todos="props.podeVerTodos"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PaeProtocolosIndexTemplate from '@/Templates/Pae/PaeProtocolosIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
    protocolos: Object,
    statistics: Object,
    filters: Object,
    statusOptions: Object,
    analistas: Array,
    empreendedores: Array,
    canAtribuir: { type: Boolean, default: false },
    canCheck: { type: Boolean, default: false },
    canPdf: { type: Boolean, default: false },
    podeVerTodos: { type: Boolean, default: false },
});
</script>
