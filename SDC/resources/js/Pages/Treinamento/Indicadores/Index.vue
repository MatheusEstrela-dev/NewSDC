<template>
  <Head title="Capacitações — Painel" />
  <div class="w-full space-y-6 pb-8">
    <PageHeader
      title="Painel de Capacitações"
      description="Indicadores de participantes, municípios e regiões atendidas pelas capacitações"
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
      :espaco-inferior="false"
    >
      <template #actions>
        <GerarRelatorioMenu :carregando="gerandoRelatorio" @gerar="gerarRelatorio" />
      </template>
    </PageHeader>

    <FiltrosAtivosFaixa :filtros="filtros" :opcoes="opcoes" @remover="(chave) => alterarFiltro({ chave, valor: '' })" @limpar="limparFiltros" />

    <div :class="{ 'opacity-70 transition-opacity': carregando }" :aria-busy="carregando" class="min-w-0 space-y-6">
      <!-- Bloco principal: destaque + mini cards a esquerda; perfil, cobertura, serie mensal e tabela a direita -->
      <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
        <div class="min-w-0 space-y-4">
          <DestaqueVisaoGeralPainel :kpis="kpis" :filtros="filtros" :opcoes="opcoes" @selecionar="selecionarCapacitacao" />
          <CapacitacoesMiniCards :por-capacitacao="porCapacitacao" :por-mes="porMes" @selecionar="selecionarCapacitacao" />
        </div>

        <div class="min-w-0 space-y-4 xl:col-span-2">
          <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <PerfilSexoPainel class="min-w-0" :por-sexo="porSexo" />
            <CoberturaAneisPainel class="min-w-0" :kpis="kpis" />
          </div>
          <CapacitadosPorMesPainel :por-mes="porMes" :filtros="filtros" :opcoes="opcoes" @alterar="alterarFiltro" />
          <TabelaDetalhadaPainel
            :tabela="tabela"
            :carregando="carregando"
            :filtros="filtros"
            :opcoes="opcoes"
            @pagina="irParaPagina"
            @alterar="alterarFiltro"
            @limpar="limparFiltros"
          />
        </div>
      </div>

      <!-- Territorial -->
      <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <MapaTerritorialPainel class="min-w-0 xl:col-span-2" :mapa="mapa" :sem-municipio="semMunicipio" />
        <PorRedecPainel class="min-w-0" :por-redec="porRedec" />
      </div>

      <!-- Estrategico -->
      <IndicadoresEstrategicosPainel :kpis="kpis" />

      <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-2">
        <RankingPainel
          class="min-w-0"
          rotulo="Ranking"
          titulo="Municípios com mais capacitados"
          subtitulo="Os 20 municípios com mais participantes"
          :icone="MapIcon"
          :linhas="linhasMunicipios"
          cor-barra="bg-emerald-500"
          vazio-titulo="Nenhum município no recorte"
          vazio-ajuda="Não há capacitados com município de realização para os filtros escolhidos."
        />
        <div class="min-w-0 space-y-4">
          <RankingPainel
            rotulo="Ranking"
            titulo="REDECs com mais capacitados"
            subtitulo="Participantes e capacitações por região"
            :icone="BuildingIcon"
            :linhas="linhasRedecs"
            vazio-titulo="Nenhuma REDEC no recorte"
            vazio-ajuda="Os capacitados do recorte não estão ligados a uma REDEC."
          />
          <CapacitacoesPorAnoPainel :por-ano="porAno" />
        </div>
      </div>
    </div>

    <PrintIndicadoresModal :show="pdfAberto" :relatorio="relatorio" :filtros="resumoFiltros" @close="pdfAberto = false" />
  </div>
</template>

<script setup>
/**
 * Painel de indicadores do modulo Capacitacoes.
 *
 * Todo numero vem pronto do servidor (TreinamentoIndicadoresController); a
 * pagina so repassa cada prop ao seu painel e recarrega quando o filtro ou a
 * pagina da tabela mudam. Os filtros vivem na URL: F5 e link compartilhado
 * mantem o recorte.
 */
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import GerarRelatorioMenu from '@/Components/Organisms/Treinamento/Indicadores/GerarRelatorioMenu.vue';
import PrintIndicadoresModal from '@/Components/Organisms/Treinamento/Indicadores/Print/PrintIndicadoresModal.vue';
import FiltrosAtivosFaixa from '@/Components/Organisms/Treinamento/Indicadores/FiltrosAtivosFaixa.vue';
import DestaqueVisaoGeralPainel from '@/Components/Organisms/Treinamento/Indicadores/DestaqueVisaoGeralPainel.vue';
import CapacitacoesMiniCards from '@/Components/Organisms/Treinamento/Indicadores/CapacitacoesMiniCards.vue';
import PerfilSexoPainel from '@/Components/Organisms/Treinamento/Indicadores/PerfilSexoPainel.vue';
import CoberturaAneisPainel from '@/Components/Organisms/Treinamento/Indicadores/CoberturaAneisPainel.vue';
import CapacitadosPorMesPainel from '@/Components/Organisms/Treinamento/Indicadores/CapacitadosPorMesPainel.vue';
import TabelaDetalhadaPainel from '@/Components/Organisms/Treinamento/Indicadores/TabelaDetalhadaPainel.vue';
import MapaTerritorialPainel from '@/Components/Organisms/Treinamento/Indicadores/MapaTerritorialPainel.vue';
import PorRedecPainel from '@/Components/Organisms/Treinamento/Indicadores/PorRedecPainel.vue';
import IndicadoresEstrategicosPainel from '@/Components/Organisms/Treinamento/Indicadores/IndicadoresEstrategicosPainel.vue';
import RankingPainel from '@/Components/Organisms/Treinamento/Indicadores/RankingPainel.vue';
import CapacitacoesPorAnoPainel from '@/Components/Organisms/Treinamento/Indicadores/CapacitacoesPorAnoPainel.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useFiltrosAtivos } from '@/Composables/treinamento/useFiltrosAtivos';
import { baixarIndicadoresCsv } from '@/Composables/treinamento/exportarIndicadoresCsv';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  filtros: { type: Object, default: () => ({ ano: null, redec_id: null, municipio_id: null, treinamento_id: null, tipo: null }) },
  opcoes: { type: Object, default: () => ({ anos: [], redecs: [], municipios: [], treinamentos: [], tipos: [] }) },
  kpis: {
    type: Object,
    default: () => ({
      total_capacitacoes: 0,
      total_participantes: 0,
      municipios_atendidos: 0,
      redecs_atendidas: 0,
      participacao_feminina_pct: 0,
      participacao_masculina_pct: 0,
      sexo_nao_informado: 0,
      capacitados_por_100k: null,
      pct_municipios_mg: 0,
      pct_redecs: 0,
      media_participantes_por_capacitacao: 0,
    }),
  },
  mapa: { type: Array, default: () => [] },
  semMunicipio: { type: Object, default: () => ({ participantes: 0, capacitacoes: 0 }) },
  porRedec: { type: Array, default: () => [] },
  porMes: { type: Array, default: () => [] },
  porAno: { type: Array, default: () => [] },
  porSexo: { type: Object, default: () => ({ feminino: 0, masculino: 0, nao_informado: 0 }) },
  porCapacitacao: { type: Array, default: () => [] },
  rankingMunicipios: { type: Array, default: () => [] },
  /** Lazy: so chega quando o botao "Gerar relatorio" pede (visita parcial). */
  relatorio: { type: Object, default: null },
  tabela: { type: Object, default: () => ({ data: [], meta: null }) },
});

// `opcoes` fica de fora: as listas dos selects nao mudam com o recorte, e
// recarregar todos os municipios de MG a cada clique seria peso sem ganho.
const PROPS_DE_DADOS = [
  'filtros', 'kpis', 'mapa', 'semMunicipio', 'porRedec', 'porMes', 'porAno',
  'porSexo', 'porCapacitacao', 'rankingMunicipios', 'tabela',
];

const carregando = ref(false);
const gerandoRelatorio = ref(false);
const pdfAberto = ref(false);

const chipsAtivos = useFiltrosAtivos(() => props.filtros, () => props.opcoes);
const resumoFiltros = computed(() => chipsAtivos.value.map((c) => `${c.rotulo}: ${c.texto}`).join(', '));

// So vai para a URL o filtro preenchido: o servidor trata ausente e vazio igual,
// mas a barra de enderecos fica limpa.
function parametrosDe(filtros) {
  return Object.fromEntries(
    Object.entries(filtros).filter(([, valor]) => valor !== null && valor !== undefined && valor !== ''),
  );
}

function recarregar(parametros) {
  router.get(route('treinamentos.indicadores'), parametros, {
    only: PROPS_DE_DADOS,
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => { carregando.value = true; },
    onFinish: () => { carregando.value = false; },
  });
}

// Mudar filtro volta para a pagina 1 da tabela: a pagina 3 do recorte antigo
// pode nem existir no novo.
function alterarFiltro({ chave, valor }) {
  recarregar(parametrosDe({ ...props.filtros, [chave]: valor }));
}

const limparFiltros = () => recarregar({});

// Clicar num mini card ou escolher no destaque filtra por aquela capacitacao.
const selecionarCapacitacao = (id) => alterarFiltro({ chave: 'treinamento_id', valor: id });

function irParaPagina(pagina) {
  recarregar({ ...parametrosDe(props.filtros), page: pagina });
}

// O relatorio nao vem na carga normal (tem todas as linhas, sem paginar): pede-se
// so ele, na mesma URL, entao os filtros atuais valem sem reenviar nada.
function gerarRelatorio(formato) {
  gerandoRelatorio.value = true;
  router.reload({
    only: ['relatorio'],
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      if (formato === 'pdf') {
        pdfAberto.value = true;
      } else {
        baixarIndicadoresCsv(page.props.relatorio?.linhas ?? []);
      }
    },
    onFinish: () => { gerandoRelatorio.value = false; },
  });
}

const rotuloContagem = (qtd, singular, plural) => `${formatarNumero(qtd)} ${qtd === 1 ? singular : plural}`;

const linhasMunicipios = computed(() => props.rankingMunicipios.map((m) => ({
  chave: m.municipio_id,
  nome: m.nome,
  detalhe: [m.redec, rotuloContagem(m.capacitacoes, 'capacitação', 'capacitações')].filter(Boolean).join(' · '),
  participantes: m.participantes,
})));

const linhasRedecs = computed(() => props.porRedec.map((r) => ({
  chave: r.redec_id,
  nome: r.nome,
  detalhe: rotuloContagem(r.capacitacoes, 'capacitação', 'capacitações'),
  participantes: r.participantes,
})));
</script>
