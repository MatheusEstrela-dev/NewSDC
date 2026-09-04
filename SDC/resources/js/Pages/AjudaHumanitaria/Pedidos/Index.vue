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
// ZiggyVue registra route() apenas em globalProperties, o que so alcanca o
// template. Em <script setup> a funcao precisa ser importada.
import { route } from 'ziggy-js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PedidoAhIndexTemplate from '@/Templates/AjudaHumanitaria/PedidoAhIndexTemplate.vue';
import { useAtualizacaoAoVivo } from '@/Composables/useAtualizacaoAoVivo';

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

/*
 * A fila reflete a tramitacao de outro usuario sem F5.
 *
 * `estatisticas` entra junto porque e closure de agregacao SEM cache: rebuscar
 * as duas mantem tabela e contadores coerentes. E o oposto do RAT, cujo
 * `statistics` fica de fora justamente por ser cacheado por 5 minutos.
 *
 * O canal e global porque a listagem tambem e: `PedidoAhService::listar()` nao
 * aplica recorte de municipio, so os filtros que o usuario manda. Se isso mudar,
 * o recurso precisa passar a ser escopado em CanaisDeListagem -- canal global
 * sobre listagem recortada e vazamento de escopo.
 */
useAtualizacaoAoVivo({
  canal: 'listagem.pedidos-ah',
  evento: '.RecursoAtualizado',
  props: ['pedidos', 'estatisticas'],
});

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
  router.get(route('ajuda-humanitaria.pedidos.create'));
}

function visualizar(id) {
  router.get(route('ajuda-humanitaria.pedidos.show', id));
}
</script>
