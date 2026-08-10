<template>
  <AuthenticatedLayout>
    <Head title="Entradas de Material" />

    <EntradaAhIndexTemplate
      :entradas="entradas"
      :estatisticas="estatisticas"
      :depositos="depositos"
      :fontes="fontes"
      :filtros="filtros"
      @filtrar="filtrar"
      @pagina="irParaPagina"
      @ver="ver"
      @exportar="exportar"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EntradaAhIndexTemplate from '@/Templates/AjudaHumanitaria/EntradaAhIndexTemplate.vue';

const props = defineProps({
  entradas: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  fontes: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.entradas.index';

function limpar(parametros) {
  return Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );
}

function navegar(parametros) {
  router.get(route(rotaIndex), limpar(parametros), { preserveState: true, preserveScroll: true, replace: true });
}

function filtrar(filtros) {
  navegar(filtros);
}

function irParaPagina(pagina) {
  navegar({ ...props.filtros, page: pagina });
}

function ver(id) {
  router.get(route('ajuda-humanitaria.entradas.show', id));
}

/**
 * escopo.all significa serie completa: as datas do modal caem, mas o recorte
 * montado na tela permanece.
 */
function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  window.location.href = route('ajuda-humanitaria.entradas.export', limpar(parametros));
}
</script>
