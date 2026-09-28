<template>
  <Head title="Painel de demandas" />
  <DemandasDashboardTemplate v-bind="$props"
    @exportar="exportar"
    @abrir="(id) => router.visit(route('demandas.show', id))"
    @filtrar-etapa="(e) => router.visit(route('demandas.index', { etapa: e }))" />
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasDashboardTemplate from '@/Templates/Demandas/DemandasDashboardTemplate.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  estatisticas: { type: Object, required: true },
  serie: { type: Array, required: true },
  recentes: { type: Array, default: () => [] },
  pode: { type: Object, required: true },
});

function exportar() {
  window.location.href = route('demandas.dashboard.export');
}

useAtualizacaoAoVivo({ canal: 'listagem.demandas', evento: '.RecursoAtualizado', props: ['estatisticas', 'serie', 'recentes'] });
</script>
