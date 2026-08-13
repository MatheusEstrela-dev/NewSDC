<template>
  <AuthenticatedLayout>
    <Head title="Movimentações de Estoque" />

    <MovimentoAhIndexTemplate
      :movimentos="movimentos"
      :estatisticas="estatisticas"
      :depositos="depositos"
      :opcoes-tipo="opcoesTipo"
      :filtros="filtros"
      :ordenacao="ordenacao"
      @filtrar="filtrar"
      @ordenar="ordenar"
      @pagina="irParaPagina"
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
import MovimentoAhIndexTemplate from '@/Templates/AjudaHumanitaria/MovimentoAhIndexTemplate.vue';

const props = defineProps({
  movimentos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesTipo: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.movimentos.index';

function limpar(parametros) {
  return Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );
}

function navegar(parametros) {
  router.get(route(rotaIndex), limpar(parametros), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/** Substituicao, nao mesclagem: limpar filtros emite {} e precisa zerar. */
function filtrar(filtros) {
  navegar(filtros);
}

function ordenar({ sort, direction }) {
  navegar({ ...props.filtros, sort, direction });
}

function irParaPagina(pagina) {
  navegar({ ...props.filtros, page: pagina });
}

/**
 * escopo.all significa serie completa: as datas do modal caem, mas o recorte
 * montado na tela (deposito, tipo, material) permanece.
 */
function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  window.location.href = route('ajuda-humanitaria.movimentos.export', limpar(parametros));
}
</script>
