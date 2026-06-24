<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PmdaCrudTemplate from '@/Templates/Pmda/PmdaCrudTemplate.vue';
import PmdaPlanosTable from '@/Components/Organisms/Pmda/PmdaPlanosTable.vue';

defineOptions({ layout: AuthenticatedLayout });
defineProps({
  planos: { type: Object, default: () => ({ data: [] }) },
  filtros: { type: Object, default: () => ({}) },
});

function editar(id) {
  router.visit(route('pmda.planos.edit', id));
}

function copiar(id) {
  router.post(route('pmda.planos.copiar', id));
}
</script>

<template>
  <Head title="PMDA — Planos" />
  <PmdaCrudTemplate title="Planos Municipais de Defesa Agropecuária">
    <PmdaPlanosTable :planos="planos" @edit="editar" @copiar="copiar" />
  </PmdaCrudTemplate>
</template>
