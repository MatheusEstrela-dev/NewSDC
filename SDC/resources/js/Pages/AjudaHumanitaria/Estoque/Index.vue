<template>
  <AuthenticatedLayout>
    <Head title="Estoque de Ajuda Humanitária" />

    <EstoqueAhIndexTemplate
      :saldos="saldos"
      :estatisticas="estatisticas"
      :depositos="depositos"
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
import EstoqueAhIndexTemplate from '@/Templates/AjudaHumanitaria/EstoqueAhIndexTemplate.vue';

const props = defineProps({
  saldos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.estoque.index';

function navegar(parametros) {
  const limpos = Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );

  router.get(route(rotaIndex), limpos, { preserveState: true, preserveScroll: true, replace: true });
}

function filtrar(filtros) {
  // Trocar de filtro volta para a primeira pagina: manter a pagina anterior
  // costuma cair fora do novo conjunto e exibir lista vazia sem motivo.
  navegar(filtros);
}

/**
 * Ordenacao vai na URL junto dos filtros: a listagem e paginada no banco, e
 * reordenar no cliente reordenaria apenas a pagina visivel. Volta para a
 * primeira pagina, porque a linha que estava na pagina 12 muda de lugar.
 */
function ordenar({ sort, direction }) {
  navegar({ ...props.filtros, sort, direction });
}

function irParaPagina(pagina) {
  navegar({ ...props.filtros, page: pagina });
}

/**
 * Leva para o CSV o mesmo recorte que esta na tela, mais o escopo do modal.
 *
 * Navegacao direta, e nao router.get: a resposta e um download, e o Inertia
 * nao sabe o que fazer com um corpo que nao e pagina.
 *
 * escopo.all significa serie completa: nesse caso as datas do modal sao
 * descartadas, mas os filtros de deposito, material e nivel permanecem, porque
 * sao o recorte que o usuario montou na tela.
 */
function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  const limpos = Object.fromEntries(
    Object.entries(parametros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );

  window.location.href = route('ajuda-humanitaria.estoque.export', limpos);
}
</script>
