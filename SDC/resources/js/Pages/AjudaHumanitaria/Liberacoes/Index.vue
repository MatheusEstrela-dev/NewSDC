<template>
  <AuthenticatedLayout>
    <Head title="Liberações de Material" />

    <LiberacaoAhIndexTemplate
      :liberacoes="liberacoes"
      :estatisticas="estatisticas"
      :depositos="depositos"
      :opcoes-status="opcoesStatus"
      :filtros="filtros"
      :ordenacao="ordenacao"
      @filtrar="filtrar"
      @ordenar="ordenar"
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
import LiberacaoAhIndexTemplate from '@/Templates/AjudaHumanitaria/LiberacaoAhIndexTemplate.vue';

const props = defineProps({
  liberacoes: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.liberacoes.index';

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

/**
 * Trocar de filtro volta para a primeira pagina: com 144 paginas, manter a
 * pagina atual quase sempre cairia fora do novo conjunto.
 */
function filtrar(filtros) {
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

function ver(id) {
  router.get(route('ajuda-humanitaria.liberacoes.show', id));
}

/**
 * escopo.all significa serie completa: as datas do modal caem, mas o recorte
 * montado na tela (deposito, situacao, busca) permanece.
 */
function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  window.location.href = route('ajuda-humanitaria.liberacoes.export', limpar(parametros));
}
</script>
