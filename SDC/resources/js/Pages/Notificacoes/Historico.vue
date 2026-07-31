<template>
    <div>
        <Head title="Notificações" />

        <HistoricoTemplate
            :notificacoes="notificacoes"
            :filtros="filtros"
            :modulos="modulos"
        />
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HistoricoTemplate from '@/Templates/Notificacoes/HistoricoTemplate.vue';

// Mesmo layout persistente dos demais modulos: sidebar, topbar e breadcrumb.
defineOptions({ layout: AuthenticatedLayout });

defineProps({
    notificacoes: { type: Object, required: true },
    filtros: { type: Object, default: () => ({}) },
    modulos: { type: Array, default: () => [] },
});
</script>
