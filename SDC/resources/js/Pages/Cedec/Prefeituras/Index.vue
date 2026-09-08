<template>
  <Head title="Cadastro de Prefeituras" />

  <!--
    Raiz com w-full space-y-6 pb-8, SEM p-* nem px-*. A calha horizontal e do <main>
    do AuthenticatedLayout (px-4 sm:px-6 lg:px-8); repetir aqui deixaria o modulo
    48px mais estreito que o resto do sistema em lg.

    Como o ritmo vem do space-y-6 do pai, todo filho com margem propria desliga a
    sua -- e o que fazem o :espaco-inferior="false" do PageHeader e o mesmo, ja
    embutido, no PrefeituraStatCards.
  -->
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      title="Cadastro de Prefeituras"
      description="Contatos institucionais das prefeituras de Minas Gerais, mantidos pela CEDEC estadual."
      :icon-image="moduleIcon('prefeituras') ?? ''"
      :icon="BuildingOffice2Icon"
      variant="gradient"
      :espaco-inferior="false"
    />
    <!--
      Sem slot #actions nesta fase, de proposito: o unico botao candidato seria
      "Relatorios de contato", cuja pagina (Cedec/Contatos/Index) so nasce na fase 5.
      Botao apontando para pagina inexistente e link morto em producao.
    -->

    <PrefeituraStatCards :estatisticas="estatisticas" @filter="filtrarPorPendencia" />

    <PrefeituraFiltersSection
      :filters="filtros"
      :redecs="redecs"
      :macrorregioes="macrorregioes"
      @apply="aplicar"
      @clear="limpar"
    />

    <!--
      Lista e paginacao num bloco so: o Pagination ja traz mt-4 proprio, e solto
      dentro do space-y-6 os dois espacos somariam 40px onde o resto da pagina usa 24px.
    -->
    <div>
      <PrefeituraTable
        :prefeituras="prefeituras.data ?? []"
        :pode-editar="can('cedec.prefeituras.edit')"
      />

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BuildingOffice2Icon } from '@heroicons/vue/24/outline';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { moduleIcon } from '@/Support/moduleIcons';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PrefeituraStatCards from '@/Components/Organisms/Cedec/PrefeituraStatCards.vue';
import PrefeituraFiltersSection from '@/Components/Organisms/Cedec/PrefeituraFiltersSection.vue';
import PrefeituraTable from '@/Components/Organisms/Cedec/PrefeituraTable.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  prefeituras: {
    type: Object,
    default: () => ({ data: [], current_page: 1, last_page: 1, per_page: 20, total: 0, from: null, to: null }),
  },
  estatisticas: {
    type: Object,
    default: () => ({ total: 0, sem_email: 0, sem_telefone: 0, sem_foto: 0 }),
  },
  filtros: { type: Object, default: () => ({}) },
  redecs: { type: Array, default: () => [] },
  macrorregioes: { type: Array, default: () => [] },
});

/**
 * O Pagination recebe UM objeto com as chaves achatadas. O backend da fase 2 ja
 * entrega nesse formato, sem meta aninhado; este computed existe para garantir os
 * defaults quando a prop chega vazia numa visita parcial.
 */
const paginacao = computed(() => ({
  current_page: props.prefeituras?.current_page ?? 1,
  last_page: props.prefeituras?.last_page ?? 1,
  per_page: props.prefeituras?.per_page ?? 20,
  total: props.prefeituras?.total ?? 0,
  from: props.prefeituras?.from ?? null,
  to: props.prefeituras?.to ?? null,
}));

/**
 * Pesquisar preserva a pendencia escolhida no stat card: busca textual e
 * REFINAMENTO, nao troca de eixo.
 */
function aplicar(filtrosDoFormulario) {
  buscar({ ...filtrosDoFormulario, pendencia: props.filtros?.pendencia ?? '' });
}

/**
 * Filtro vindo de stat card SUBSTITUI o eixo: clicar em "Sem e-mail" tem que
 * mostrar quem esta sem e-mail, nao a intersecao com a pendencia anterior. O que
 * sobrevive e o recorte territorial e a busca, que sao refinamento.
 */
function filtrarPorPendencia(pendencia) {
  buscar({
    busca: props.filtros?.busca ?? '',
    redec_id: props.filtros?.redec_id ?? '',
    macrorregiao: props.filtros?.macrorregiao ?? '',
    pendencia: pendencia ?? '',
  });
}

function limpar() {
  buscar({});
}

function irParaPagina(pagina) {
  buscar({ ...props.filtros, page: pagina });
}

/**
 * Reload PARCIAL: estatisticas fica FORA do only.
 *
 * No controller ela e uma closure, e closure de prop no Inertia e reavaliada em TODA
 * visita completa. Sem isto, os quatro contadores -- que agregam sobre as 853 linhas
 * de municipio com LEFT JOIN em compdec_prefeituras -- seriam recalculados a cada
 * troca de filtro e a cada pagina, sem precisar: eles medem o cadastro inteiro, nao
 * o resultado filtrado.
 *
 * redecs e macrorregioes tambem ficam de fora: sao listas fixas.
 */
function buscar(filtros) {
  router.get(route('cedec.prefeituras.index'), paraQuery(filtros), {
    only: ['prefeituras', 'filtros'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Tira o que esta vazio, para a URL nao encher de parametro sem valor -- e para o
 * PrefeituraFiltroDTO receber null em vez de string vazia.
 */
function paraQuery(filtros) {
  const query = {};

  Object.entries(filtros ?? {}).forEach(([chave, valor]) => {
    if (valor === undefined || valor === null || valor === '') return;

    query[chave] = valor;
  });

  return query;
}
</script>
