<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Beneficiarios" />

    <div class="space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Gestao de Cisternas"
        description="Cadastro de beneficiarios e fiscalizacao da instalacao em tres etapas"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <ActionButton
            v-if="permissoes.exportar"
            action="export"
            variant="success"
            label="Exportar"
            :allowed="true"
            @click="exportar"
          />
          <ActionButton
            v-if="permissoes.criar"
            action="create"
            module="cisternas"
            resource="beneficiarios"
            label="Novo cadastro"
            :allowed="true"
            @click="router.visit(route('cisternas.beneficiarios.create'))"
          />
        </template>
      </PageHeader>

      <CisternaStatisticsCards
        v-if="indicadores"
        :indicadores="indicadores"
        @filter="filtrarPorCard"
      />

      <BeneficiarioFiltersSection
        :filtros="filtros"
        :municipios="opcoes.municipios ?? []"
        :situacoes-analise="opcoes.situacoes_analise ?? []"
        :situacoes-obra="opcoes.situacoes_obra ?? []"
        :etapas-vistoria="opcoes.etapas_vistoria ?? []"
        @apply="aplicar"
        @clear="limpar"
      />

      <BeneficiariosTable
        v-model:marcados="marcados"
        :beneficiarios="beneficiarios.data ?? []"
        :selecionavel="perfil.e_cedec || perfil.e_compdec"
      />

      <Pagination :pagination="paginacao" @page-change="irParaPagina" />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import CisternaStatisticsCards from '@/Components/Organisms/Cisterna/CisternaStatisticsCards.vue';
import BeneficiarioFiltersSection from '@/Components/Organisms/Cisterna/BeneficiarioFiltersSection.vue';
import BeneficiariosTable from '@/Components/Organisms/Cisterna/BeneficiariosTable.vue';

const props = defineProps({
  beneficiarios: { type: Object, required: true },
  indicadores: { type: Object, default: null },
  filtros: { type: Object, default: () => ({}) },
  opcoes: { type: Object, default: () => ({}) },
  perfil: { type: Object, default: () => ({}) },
  permissoes: { type: Object, default: () => ({}) },
});

const marcados = ref([]);

const paginacao = computed(() => {
  const m = props.beneficiarios?.meta;

  if (!m) return null;

  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 25,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

/**
 * Download por navegacao direta, e nao router.visit: a resposta e um CSV
 * streamado, e o Inertia esperaria uma pagina.
 */
function exportar() {
  const query = new URLSearchParams(paraQuery(props.filtros)).toString();

  window.location.href = `${route('cisternas.beneficiarios.export')}?${query}`;
}

/**
 * Filtro vindo de stat card SUBSTITUI os filtros de eixo, em vez de somar.
 *
 * Clicar em "Aprovados" tem que mostrar os aprovados, nao a intersecao com o que
 * ja estava filtrado antes -- foi assim que o menu do legado funcionava, e e o
 * que a convencao de card-como-atalho quer dizer. O que se preserva e a busca
 * textual, que e refinamento e nao eixo.
 */
function filtrarPorCard(filtro) {
  const preservado = {
    search: props.filtros.search,
    cpf: props.filtros.cpf,
    numero_instalacao: props.filtros.numero_instalacao,
    municipio_id: props.filtros.municipio_id,
  };

  buscar({ ...preservado, ...filtro });
}

function irParaPagina(page) {
  buscar({ ...props.filtros, page });
}

function aplicar(filtros) {
  buscar(filtros);
}

function limpar() {
  buscar({});
}

/**
 * Reload PARCIAL: `indicadores` fica fora do `only`.
 *
 * No controller ele e uma closure, e closure de prop no Inertia e avaliada em
 * TODA visita completa -- ou seja, sem isto os 11 contadores (que fazem
 * agregacao sobre 8.096 linhas com FILTER e tres subconsultas de etapa) seriam
 * recalculados a cada troca de filtro, sem precisar: eles medem o escopo do
 * perfil, nao o resultado filtrado.
 */
function buscar(filtros) {
  marcados.value = [];

  router.get(route('cisternas.beneficiarios.index'), paraQuery(filtros), {
    only: ['beneficiarios', 'filtros'],
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

/**
 * Tira o que esta vazio, para a URL nao encher de parametro sem valor, e manda
 * booleano como 1/0 -- que e o que o controller le.
 */
function paraQuery(filtros) {
  const query = {};

  Object.entries(filtros ?? {}).forEach(([chave, valor]) => {
    if (valor === undefined || valor === null || valor === '') return;
    if (Array.isArray(valor) && valor.length === 0) return;
    if (valor === false) return;

    query[chave] = valor === true ? 1 : valor;
  });

  return query;
}
</script>
