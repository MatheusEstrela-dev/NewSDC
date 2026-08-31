<template>
  <AuthenticatedLayout>
    <Head title="Transferências entre Depósitos" />

    <TransferenciaAhIndexTemplate
      :transferencias="transferencias"
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
import TransferenciaAhIndexTemplate from '@/Templates/AjudaHumanitaria/TransferenciaAhIndexTemplate.vue';

const props = defineProps({
  transferencias: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  depositos: { type: Array, default: () => [] },
  opcoesStatus: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
  ordenacao: { type: Object, default: () => ({}) },
});

const rotaIndex = 'ajuda-humanitaria.transferencias.index';

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
  router.get(route('ajuda-humanitaria.transferencias.show', id));
}

function exportar(escopo = {}) {
  const parametros = { ...props.filtros };

  if (!escopo.all && escopo.data_inicio && escopo.data_fim) {
    parametros.data_inicio = escopo.data_inicio;
    parametros.data_fim = escopo.data_fim;
  }

  window.location.href = route('ajuda-humanitaria.transferencias.export', limpar(parametros));
}
</script>
