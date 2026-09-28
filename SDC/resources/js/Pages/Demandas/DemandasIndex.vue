<template>
  <DemandasIndexTemplate
    :demandas="demandas.data"
    :paginacao="demandas"
    :estatisticas="estatisticas"
    :filtros="local"
    :opcoes="opcoes"
    :pode="pode"
    @criar="router.visit(route('demandas.create'))"
    @exportar="exportar"
    @aplicar="aplicar()"
    @limpar="limpar"
    @filtrar-etapa="filtrarEtapa"
    @abrir="(id) => router.visit(route('demandas.show', id))"
    @pagina="(p) => aplicar({ page: p })"
  />
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DemandasIndexTemplate from '@/Templates/Demandas/DemandasIndexTemplate.vue';
import { useDemandaFilters } from '@/Composables/demandas';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  demandas: { type: Object, required: true },
  estatisticas: { type: Object, required: true },
  filtros: { type: Object, default: () => ({}) },
  opcoes: { type: Object, required: true },
  pode: { type: Object, required: true },
});

const { local, aplicar, limpar, filtrarEtapa } = useDemandaFilters(props.filtros);
useAtualizacaoAoVivo({ canal: 'listagem.demandas', evento: '.RecursoAtualizado', props: ['demandas', 'estatisticas'] });

function exportar() {
  window.location.href = route('admin.demandas.export', Object.fromEntries(Object.entries(local).filter(([, v]) => v !== '')));
}
</script>
