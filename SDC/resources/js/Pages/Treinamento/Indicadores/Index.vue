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
      <!-- 1. Visao geral (filtros de curso, turma e situacao + totais) e perfil do publico -->
      <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
        <DestaqueVisaoGeralPainel class="min-w-0" :kpis="kpis" :filtros="filtros" :opcoes="opcoes" @alterar="alterarFiltros" />
        <PerfilPublicoPainel class="min-w-0 xl:col-span-2" :por-sexo="porSexo" :por-faixa-etaria="porFaixaEtaria" />
      </div>

      <!-- 2. Cards por curso e serie mensal com detalhe do mes clicado -->
      <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-3">
        <CursosCardsPainel class="min-w-0" :por-curso="porCurso" :filtros="filtros" @selecionar="alterarFiltros" />
        <CapacitadosPorMesPainel
          class="min-w-0 xl:col-span-2"
          :por-mes="porMes"
          :filtros="filtros"
          :opcoes="opcoes"
          :mes-selecionado="mesSelecionado"
          :detalhe-mes="detalheMesCarregado === mesSelecionado ? detalheMes : null"
          @alterar="alterarFiltro"
          @selecionar-mes="selecionarMes"
        />
      </div>

      <!-- 3. Satisfacao -->
      <SatisfacaoPainel :satisfacao="satisfacao" :filtros="filtros" />

      <!-- 4 e 5. Cobertura territorial e o mapa logo abaixo, ligados pela REDEC destacada -->
      <CoberturaTerritorialPainel :kpis="kpis" :por-redec="porRedec" :redec-destacada="redecDestacada" @selecionar-redec="destacarRedec" />
      <MapaTerritorialPainel
        class="min-w-0"
        :mapa="mapa"
        :territorio="territorio"
        :sem-municipio="semMunicipio"
        :redecs="opcoes.redecs ?? []"
        :redec-destacada="redecDestacada"
        @selecionar-redec="destacarRedec"
      />

      <!-- 6. Detalhamento e paineis complementares -->
      <TabelaDetalhadaPainel
        :tabela="tabela"
        :carregando="carregando"
        :filtros="filtros"
        :opcoes="opcoes"
        @pagina="irParaPagina"
        @alterar="alterarFiltro"
        @limpar="limparFiltros"
      />

      <IndicadoresEstrategicosPainel :kpis="kpis" />

      <div class="grid grid-cols-1 items-start gap-4 xl:grid-cols-2">
        <PessoasPorCursoPainel class="min-w-0" :linhas="pessoasPorCurso" />
        <PessoasPorMunicipioPainel class="min-w-0" :linhas="pessoasPorMunicipio" />
      </div>

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
          vazio-ajuda="Não há participantes com município de referência para os filtros escolhidos."
        />
        <div class="min-w-0 space-y-4">
          <RankingPainel
            rotulo="Ranking"
            titulo="REDECs com mais capacitados"
            subtitulo="Participantes e turmas por região"
            :icone="BuildingIcon"
            :linhas="linhasRedecs"
            vazio-titulo="Nenhuma REDEC no recorte"
            vazio-ajuda="Os participantes do recorte não estão ligados a uma REDEC."
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
 * Todo numero vem pronto do servidor (IndicadoresController); a pagina so
 * repassa cada prop ao seu painel e recarrega quando o filtro ou a pagina da
 * tabela mudam. Os filtros vivem na URL: F5 e link compartilhado mantem o
 * recorte. Estado so de tela (mes aberto, REDEC destacada no mapa) fica aqui.
 */
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import GerarRelatorioMenu from '@/Components/Organisms/Treinamento/Indicadores/GerarRelatorioMenu.vue';
import PrintIndicadoresModal from '@/Components/Organisms/Treinamento/Indicadores/Print/PrintIndicadoresModal.vue';
import FiltrosAtivosFaixa from '@/Components/Organisms/Treinamento/Indicadores/FiltrosAtivosFaixa.vue';
import DestaqueVisaoGeralPainel from '@/Components/Organisms/Treinamento/Indicadores/DestaqueVisaoGeralPainel.vue';
import PerfilPublicoPainel from '@/Components/Organisms/Treinamento/Indicadores/PerfilPublicoPainel.vue';
import CursosCardsPainel from '@/Components/Organisms/Treinamento/Indicadores/CursosCardsPainel.vue';
import CapacitadosPorMesPainel from '@/Components/Organisms/Treinamento/Indicadores/CapacitadosPorMesPainel.vue';
import SatisfacaoPainel from '@/Components/Organisms/Treinamento/Indicadores/SatisfacaoPainel.vue';
import CoberturaTerritorialPainel from '@/Components/Organisms/Treinamento/Indicadores/CoberturaTerritorialPainel.vue';
import MapaTerritorialPainel from '@/Components/Organisms/Treinamento/Indicadores/MapaTerritorialPainel.vue';
import TabelaDetalhadaPainel from '@/Components/Organisms/Treinamento/Indicadores/TabelaDetalhadaPainel.vue';
import IndicadoresEstrategicosPainel from '@/Components/Organisms/Treinamento/Indicadores/IndicadoresEstrategicosPainel.vue';
import RankingPainel from '@/Components/Organisms/Treinamento/Indicadores/RankingPainel.vue';
import PessoasPorCursoPainel from '@/Components/Organisms/Treinamento/Indicadores/PessoasPorCursoPainel.vue';
import PessoasPorMunicipioPainel from '@/Components/Organisms/Treinamento/Indicadores/PessoasPorMunicipioPainel.vue';
import CapacitacoesPorAnoPainel from '@/Components/Organisms/Treinamento/Indicadores/CapacitacoesPorAnoPainel.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useFiltrosAtivos } from '@/Composables/treinamento/useFiltrosAtivos';
import { baixarIndicadoresCsv } from '@/Composables/treinamento/exportarIndicadoresCsv';
import { rotuloContagem, situacoesFiltradas } from '@/Composables/treinamento/useTemaGrafico';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  filtros: {
    type: Object,
    default: () => ({ ano: null, tipo: null, redec_id: null, municipio_id: null, curso_id: null, treinamento_id: null, situacoes: [] }),
  },
  opcoes: {
    type: Object,
    default: () => ({ anos: [], redecs: [], municipios: [], cursos: [], treinamentos: [], tipos: [], situacoes: [] }),
  },
  /** Todos os municipios de MG (estatico): o mapa pinta tambem os nao atendidos. */
  territorio: { type: Array, default: () => [] },
  kpis: {
    type: Object,
    default: () => ({
      total_participantes: 0,
      concluidos: 0,
      em_andamento: 0,
      desistentes: 0,
      taxa_desistencia_pct: 0,
      total_cursos: 0,
      total_capacitacoes: 0,
      municipios_atendidos: 0,
      municipios_total: 0,
      pct_municipios_mg: 0,
      redecs_atendidas: 0,
      redecs_total: 0,
      pct_redecs: 0,
      participacao_feminina_pct: 0,
      participacao_masculina_pct: 0,
      sexo_nao_informado: 0,
      capacitados_por_100k: null,
      media_participantes_por_capacitacao: 0,
    }),
  },
  mapa: { type: Array, default: () => [] },
  semMunicipio: { type: Object, default: () => ({ participantes: 0, capacitacoes: 0 }) },
  porRedec: { type: Array, default: () => [] },
  porMes: { type: Array, default: () => [] },
  porAno: { type: Array, default: () => [] },
  porSexo: { type: Object, default: () => ({ feminino: 0, masculino: 0, nao_binario: 0, outro: 0, nao_informado: 0 }) },
  porFaixaEtaria: { type: Array, default: () => [] },
  porCurso: { type: Array, default: () => [] },
  satisfacao: { type: Object, default: () => ({ n: 0, media: null, distribuicao: [], por_curso: [], por_turma: [] }) },
  rankingMunicipios: { type: Array, default: () => [] },
  pessoasPorCurso: { type: Array, default: () => [] },
  pessoasPorMunicipio: { type: Array, default: () => [] },
  /** Lazy: so chega quando um mes do grafico mensal e clicado (visita parcial). */
  detalheMes: { type: Object, default: null },
  /** Lazy: so chega quando o botao "Gerar relatorio" pede (visita parcial). */
  relatorio: { type: Object, default: null },
  tabela: { type: Object, default: () => ({ data: [], meta: null }) },
});

// Fora do reload: `opcoes` e `territorio` nao mudam com o recorte (recarregar
// todos os municipios de MG a cada clique seria peso sem ganho), e detalheMes e
// relatorio sao lazy, pedidos so pela propria acao.
const PROPS_DE_DADOS = [
  'filtros', 'kpis', 'mapa', 'semMunicipio', 'porRedec', 'porMes', 'porAno', 'porSexo', 'porFaixaEtaria',
  'porCurso', 'satisfacao', 'rankingMunicipios', 'pessoasPorCurso', 'pessoasPorMunicipio', 'tabela',
];

const carregando = ref(false);
const gerandoRelatorio = ref(false);
const pdfAberto = ref(false);
const mesSelecionado = ref(null);
// Mes cuja resposta chegou DEPOIS da ultima selecao: o detalheMes que ficou na
// pagina pode ser de um recorte anterior e nao deve aparecer.
const detalheMesCarregado = ref(null);
const redecDestacada = ref(null);

const chipsAtivos = useFiltrosAtivos(() => props.filtros, () => props.opcoes);
const resumoFiltros = computed(() => chipsAtivos.value.map((c) => `${c.rotulo}: ${c.texto}`).join(', '));

// So vai para a URL o filtro preenchido: o servidor trata ausente e vazio igual,
// mas a barra de enderecos fica limpa. Situacoes vai como situacoes[]=X, e so
// quando restringe (as tres marcadas = sem filtro).
function parametrosDe(filtros) {
  const { situacoes, ...demais } = filtros;
  const parametros = Object.fromEntries(
    Object.entries(demais).filter(([, valor]) => valor !== null && valor !== undefined && valor !== ''),
  );
  const restritas = situacoesFiltradas(situacoes);

  return restritas.length ? { ...parametros, situacoes: restritas } : parametros;
}

function recarregar(parametros) {
  // O detalhe aberto e do recorte antigo: fecha em vez de mostrar numero velho.
  mesSelecionado.value = null;
  detalheMesCarregado.value = null;
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
// pode nem existir no novo. `parcial` traz os filtros que mudam juntos.
function alterarFiltros(parcial) {
  recarregar(parametrosDe({ ...props.filtros, ...parcial }));
}

const alterarFiltro = ({ chave, valor }) => alterarFiltros({ [chave]: valor });

const limparFiltros = () => recarregar({});

function irParaPagina(pagina) {
  recarregar({ ...parametrosDe(props.filtros), page: pagina });
}

// Clicar numa barra do grafico mensal pede so o detalhe daquele mes (prop lazy);
// null fecha. O bloco espera a resposta comparando o `mes` que volta.
function selecionarMes(mes) {
  mesSelecionado.value = mes;
  detalheMesCarregado.value = null;
  if (!mes) return;

  router.reload({
    only: ['detalheMes'],
    data: { mes },
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onSuccess: () => {
      if (mesSelecionado.value === mes) detalheMesCarregado.value = mes;
    },
  });
}

// REDEC destacada e so visual (cobertura + mapa): nao recarrega dado. Clicar
// de novo na mesma limpa.
function destacarRedec(redecId) {
  const id = redecId === null || redecId === undefined || redecId === '' ? null : Number(redecId);
  redecDestacada.value = id === null || id === redecDestacada.value ? null : id;
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

const linhasMunicipios = computed(() => props.rankingMunicipios.map((m) => ({
  chave: m.municipio_id,
  nome: m.nome,
  detalhe: [m.redec, rotuloContagem(m.capacitacoes, 'turma', 'turmas')].filter(Boolean).join(' · '),
  participantes: m.participantes,
})));

// porRedec traz todas as REDECs (inclusive zeradas, para a cobertura); o
// ranking so mostra quem teve participante, do maior para o menor.
const linhasRedecs = computed(() => props.porRedec
  .filter((r) => Number(r.participantes) > 0)
  .sort((a, b) => Number(b.participantes) - Number(a.participantes))
  .map((r) => ({
    chave: r.redec_id,
    nome: r.nome,
    detalhe: rotuloContagem(r.capacitacoes, 'turma', 'turmas'),
    participantes: r.participantes,
  })));
</script>
