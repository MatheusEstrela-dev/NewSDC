<template>
  <Head :title="remanejamento ? 'Editar remanejamento' : 'Novo remanejamento'" />
  <RemanejamentoFormTemplate
    :remanejamento="remanejamento"
    :opcoes="opcoes"
    @voltar="router.visit(route('inventario.movimentacoes.index'))"
  />
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RemanejamentoFormTemplate from '@/Templates/Inventario/RemanejamentoFormTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  remanejamento: { type: Object, default: null },
  opcoes: { type: Object, required: true },
});
</script>
