<template>
  <AuthenticatedLayout>
    <Head title="Pedidos de Ajuda Humanitária" />

    <PedidoAhIndexTemplate
      :pedidos="pedidos"
      :estatisticas="estatisticas"
      :filtros="filtros"
      :opcoes-status="opcoesStatus"
      :can-create="canCreate"
      @create="criar"
      @view="visualizar"
      @filtrar="filtrar"
      @filtrar-fase="filtrarPorFase"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PedidoAhIndexTemplate from '@/Templates/AjudaHumanitaria/PedidoAhIndexTemplate.vue';

const props = defineProps({
  pedidos: { type: Object, default: () => ({ data: [], meta: null }) },
  estatisticas: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({}) },
  opcoesStatus: { type: Array, default: () => [] },
  canCreate: { type: Boolean, default: false },
  canEdit: { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canExport: { type: Boolean, default: false },
});

const rotaIndex = 'ajuda-humanitaria.pedidos.index';

function filtrar(filtros) {
  const limpos = Object.fromEntries(
    Object.entries(filtros).filter(([, valor]) => valor !== '' && valor !== null && valor !== undefined),
  );

  router.get(route(rotaIndex), limpos, { preserveState: true, preserveScroll: true, replace: true });
}

/**
 * Os cartoes do topo agrupam varios status por fase. Enquanto o backend nao
 * expoe um filtro por fase, o clique em cartao de fase unica filtra por status
 * e o de fase composta apenas limpa o filtro.
 */
function filtrarPorFase(chave) {
  const statusPorCartao = {
    em_edicao: 0,
    atendidos: 6,
    finalizados: 9,
  };

  if (chave in statusPorCartao) {
    filtrar({ status: statusPorCartao[chave] });

    return;
  }

  filtrar({});
}

function criar() {
  // Formulario de criacao entra na proxima etapa.
}

function visualizar() {
  // Tela de detalhe entra na proxima etapa.
}
</script>
