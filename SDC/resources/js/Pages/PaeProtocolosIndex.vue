<template>
    <div>
        <Head title="Protocolos PAE" />

        <PaeProtocolosIndexTemplate
          :use-mock="useMock"
          :loading="false"
          :can-create="can('pae.protocolos.create')"
          :can-edit="can('pae.protocolos.edit')"
          :can-delete="can('pae.protocolos.delete')"
          :can-export="can('pae.protocolos.export')"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PaeProtocolosIndexTemplate from '@/Templates/Pae/PaeProtocolosIndexTemplate.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();
const page = usePage();
console.log('DEBUG: User Privileges', {
    user: page.props.auth.user.name,
    roles: page.props.auth.user.roles,
    permissions: page.props.auth.user.permissions,
    canCreate: can('pae.protocolos.create'),
    canView: can('pae.protocolos.view')
});

// Frontend-only: por enquanto, usar mocks. Depois e so trocar para false ou usar env flag.
const useMock = true;
</script>


