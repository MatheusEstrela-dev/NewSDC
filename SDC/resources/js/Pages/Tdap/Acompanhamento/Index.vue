<template>
  <Head title="TDAP — Acompanhamento de Cronogramas" />
  <div class="w-full space-y-6 pb-8">
    <!-- Cabecalho (modelo Figma "Novo modelo para tela"): sobretitulo, titulo
         grande, descricao e a acao principal a direita. -->
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div class="min-w-0">
        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600 dark:text-blue-400">TDAP · Operações</p>
        <h1 class="mt-1 text-3xl sm:text-4xl font-semibold tracking-tight text-slate-900 dark:text-white">Acompanhamento</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
          Acompanhe o progresso dos cronogramas e identifique o que precisa da sua atenção.
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-2">
        <Button
          :variant="minhaFila ? 'primary' : 'outline'"
          size="md"
          type="button"
          @click="alternarMinhaFila"
        >
          {{ minhaFila ? 'Mostrando minha fila' : 'Minha fila' }}
        </Button>
        <!-- Gerar relatorio: PDF (impressao do navegador) ou planilha CSV. -->
        <div ref="menuRelatorio" class="relative">
          <Button variant="primary" size="md" type="button" :icon="DownloadIcon" icon-position="left" @click="menuRelatorioAberto = !menuRelatorioAberto">
            Gerar relatório
            <ChevronDownIcon class="h-4 w-4" />
          </Button>
          <div
            v-if="menuRelatorioAberto"
            class="absolute right-0 z-30 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
          >
            <button type="button" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800" @click="abrirPdf">
              <DocumentTextIcon class="h-4 w-4 text-red-500" />
              <span>PDF<span class="block text-[11px] text-slate-400">Documento para imprimir ou salvar</span></span>
            </button>
            <button type="button" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800" @click="gerarCsv">
              <DownloadIcon class="h-4 w-4 text-emerald-500" />
              <span>Planilha (CSV)<span class="block text-[11px] text-slate-400">Para abrir no Excel</span></span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Indicadores: saem das proprias colunas, sem consulta extra. -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
      <div
        v-for="ind in indicadores"
        :key="ind.titulo"
        class="rounded-2xl border border-slate-200 dark:border-slate-700/50 bg-white dark:bg-slate-900/60 p-5 shadow-sm"
      >
        <div class="flex items-start gap-4">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl" :class="ind.icone_cor">
            <component :is="ind.icone" class="h-5 w-5" />
          </span>
          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
              <p class="text-sm text-slate-500 dark:text-slate-400">{{ ind.titulo }}</p>
              <p class="text-3xl font-semibold leading-none text-slate-900 dark:text-white">{{ ind.valor }}</p>
            </div>
            <span class="mt-3 inline-flex rounded-md px-2 py-0.5 text-[11px] font-medium" :class="ind.selo_cor">
              {{ ind.selo }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Fluxo de cronogramas -->
    <section class="space-y-4">
      <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
          <h2 class="text-xl font-semibold text-slate-900 dark:text-white">Fluxo de cronogramas</h2>
          <p class="text-xs text-slate-500 dark:text-slate-400">Atualizado {{ atualizadoHa }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="r in RECORTES"
            :key="r.value"
            type="button"
            class="rounded-lg border px-3 py-1.5 text-xs font-medium transition"
            :class="recorte === r.value
              ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/50 dark:bg-blue-500/15 dark:text-blue-300'
              : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'"
            @click="recorte = r.value"
          >
            {{ r.label }}
          </button>

          <select
            :value="filtroFase"
            class="rounded-lg border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs text-slate-600 focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
            title="Exibir apenas uma fase"
            @change="exibirFase($event.target.value)"
          >
            <option v-for="op in opcoesDeFase" :key="op.value" :value="op.value">{{ op.label }}</option>
          </select>

          <button
            type="button"
            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-medium transition"
            :class="mostrarFiltros || totalFiltrosGeral
              ? 'border-blue-300 bg-blue-50 text-blue-700 dark:border-blue-500/50 dark:bg-blue-500/15 dark:text-blue-300'
              : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'"
            @click="mostrarFiltros = !mostrarFiltros"
          >
            <FunnelIcon class="h-4 w-4" />
            Filtros<span v-if="totalFiltrosGeral">({{ totalFiltrosGeral }})</span>
          </button>
        </div>
      </div>

      <!-- Quadro: rolagem horizontal no celular, 5 colunas no desktop largo. -->
      <div class="-mx-4 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
        <div
          class="grid gap-4 items-start"
          :class="umaFase ? 'grid-cols-1' : 'grid-flow-col auto-cols-[minmax(17rem,1fr)] xl:grid-flow-row xl:grid-cols-5 xl:auto-cols-auto'"
        >
          <section
            v-for="col in colunas"
            :key="col.value"
            class="flex max-h-[78vh] flex-col rounded-2xl border border-t-4 border-slate-200 bg-slate-50/80 dark:border-slate-700/50 dark:bg-slate-900/40"
            :class="estiloDaFase(col.value).borda"
          >
            <header class="px-4 pt-4 pb-3">
              <div class="flex items-start gap-3">
                <span
                  class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-xs font-semibold"
                  :class="estiloDaFase(col.value).cor"
                >{{ String(col.ordem).padStart(2, '0') }}</span>
                <div class="min-w-0 flex-1">
                  <h3 class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100" :title="col.label">{{ col.label }}</h3>
                  <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">
                    {{ col.responsavel }}<span v-if="col.minha" class="text-blue-600 dark:text-blue-400"> · você age aqui</span>
                  </p>
                </div>
                <span class="rounded-md px-2 py-0.5 text-xs font-semibold" :class="estiloDaFase(col.value).cor">{{ col.total }}</span>
              </div>

              <!-- Filtros da propria coluna (abertos pelo botao "Filtros" acima). -->
              <div v-if="mostrarFiltros" class="mt-3 space-y-2">
                <div class="relative">
                  <MagnifyingGlassIcon class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                  <input
                    v-model="filtrosDa(col.value).busca"
                    type="search"
                    placeholder="Protocolo, município ou prestador"
                    class="w-full rounded-lg border-slate-200 py-1.5 pl-8 pr-2 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200"
                  />
                </div>
                <div class="grid gap-2" :class="umaFase ? 'grid-cols-2 md:grid-cols-4' : 'grid-cols-2'">
                  <select
                    v-for="campo in CAMPOS_SELECT"
                    :key="campo.chave"
                    v-model="filtrosDa(col.value)[campo.chave]"
                    :title="campo.rotulo"
                    class="w-full rounded-lg border-slate-200 py-1 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200"
                  >
                    <option value="">{{ campo.rotulo }}: todos</option>
                    <option v-for="op in opcoesDaColuna(col, campo.campo)" :key="op" :value="op">{{ op }}</option>
                  </select>
                  <select
                    v-model="filtrosDa(col.value).caminhoes"
                    class="w-full rounded-lg border-slate-200 py-1 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200"
                  >
                    <option value="">Caminhões: todos</option>
                    <option value="com">Com caminhão</option>
                    <option value="sem">Sem caminhão</option>
                  </select>
                  <select
                    v-model="filtrosDa(col.value).parado"
                    class="w-full rounded-lg border-slate-200 py-1 text-xs focus:border-blue-500 focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200"
                  >
                    <option value="">Parado: qualquer</option>
                    <option value="7">+7 dias</option>
                    <option value="15">+15 dias</option>
                    <option value="30">+30 dias</option>
                    <option value="90">+90 dias</option>
                  </select>
                  <label class="text-[10px] text-slate-500">Vigência de
                    <input v-model="filtrosDa(col.value).vigenciaDe" type="date" class="mt-0.5 w-full rounded-lg border-slate-200 py-1 text-xs dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200" />
                  </label>
                  <label class="text-[10px] text-slate-500">Vigência até
                    <input v-model="filtrosDa(col.value).vigenciaAte" type="date" class="mt-0.5 w-full rounded-lg border-slate-200 py-1 text-xs dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-200" />
                  </label>
                </div>
                <button
                  v-if="totalFiltrosAtivos(col.value)"
                  type="button"
                  class="text-[11px] text-blue-600 hover:underline dark:text-blue-400"
                  @click="limparColuna(col.value)"
                >
                  Limpar filtros desta fase ({{ totalFiltrosAtivos(col.value) }})
                </button>
              </div>
            </header>

            <div
              class="flex-1 overflow-y-auto px-3 pb-3"
              :class="umaFase ? 'grid grid-cols-1 content-start gap-3 sm:grid-cols-2 xl:grid-cols-4' : 'space-y-3'"
            >
              <button
                v-for="card in cardsVisiveis(col)"
                :key="card.id"
                type="button"
                class="w-full rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition hover:border-blue-300 hover:shadow-md dark:border-slate-700/60 dark:bg-slate-900 dark:hover:border-blue-500/50"
                @click="abrir(card)"
              >
                <div class="flex items-start justify-between gap-2">
                  <span class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{{ card.numero }}</span>
                  <span
                    v-if="card.dias_na_fase !== null"
                    class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap text-[11px]"
                    :class="emAtraso(col.value, card) ? 'font-medium text-red-600 dark:text-red-400' : 'text-slate-400'"
                    :title="`Na fase desde ${fmtData(card.desde)}`"
                  >
                    <ClockIcon v-if="emAtraso(col.value, card)" class="h-3.5 w-3.5" />
                    {{ card.dias_na_fase === 0 ? 'Hoje' : `há ${card.dias_na_fase} dias` }}
                  </span>
                </div>
                <p class="mt-2 truncate text-sm font-semibold text-slate-800 dark:text-slate-200">{{ card.municipio || '—' }}</p>
                <p class="truncate text-[11px] uppercase tracking-wide text-slate-400">{{ card.prestador || '—' }}</p>
                <div class="mt-3 flex items-center gap-1.5 border-t border-slate-100 pt-3 text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400">
                  <TruckIcon class="h-3.5 w-3.5 shrink-0 text-amber-500" />
                  <span class="truncate">Ata {{ card.ata || '—' }} · Lote {{ card.lote || '—' }} · {{ card.caminhoes }} caminhões</span>
                </div>
              </button>

              <p v-if="!col.cards.length" class="col-span-full py-8 text-center text-xs text-slate-400">Nenhum cronograma nesta fase.</p>
              <p v-else-if="!cardsVisiveis(col).length" class="col-span-full py-8 text-center text-xs text-slate-400">
                Nenhum cronograma com este filtro.
              </p>
              <p v-else-if="col.total > col.cards.length" class="col-span-full pt-1 text-center text-[11px] text-slate-400">
                Mostrando {{ col.cards.length }} de {{ col.total }}. Use os filtros da fase para refinar.
              </p>
            </div>
          </section>
        </div>
      </div>
    </section>

    <PrintAcompanhamentoModal
      :show="pdfAberto"
      :fases="fasesDoRelatorio"
      :indicadores="indicadores"
      :filtros="resumoDosFiltros"
      @close="pdfAberto = false"
    />

    <CronogramaAcompanhamentoModal
      :open="!!selecionado"
      :cronograma="selecionado"
      @close="selecionado = null"
      @tramitado="recarregarQuadro"
    />
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import BoltIcon from '@/Components/Icons/BoltIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ChevronDownIcon from '@/Components/Icons/ChevronDownIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import FunnelIcon from '@/Components/Icons/FunnelIcon.vue';
import MagnifyingGlassIcon from '@/Components/Icons/MagnifyingGlassIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CronogramaAcompanhamentoModal from '@/Components/Organisms/Tdap/CronogramaAcompanhamentoModal.vue';
import PrintAcompanhamentoModal from '@/Components/Organisms/Tdap/Print/PrintAcompanhamentoModal.vue';
import { estiloDaFase } from '@/Support/faseTramiteTdap';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  /** 5 fases (FaseCronograma) com `cards`, `total` e `minha` (TramitacaoCronogramaService::painel). */
  colunas: { type: Array, default: () => [] },
  filtros: { type: Object, default: () => ({}) },
});

const minhaFila  = ref(['1', 'true', true].includes(props.filtros.minha_fila));
const filtroFase = ref(props.filtros.fase ?? '');

// Valores de FaseCronograma; '' = quadro inteiro.
const opcoesDeFase = [
  { value: '', label: 'Todas as fases' },
  { value: 'elaboracao', label: 'Em elaboração' },
  { value: 'em_analise', label: 'Em análise' },
  { value: 'aprovado', label: 'Aprovados' },
  { value: 'em_execucao', label: 'Em execução' },
  { value: 'entregue', label: 'Entregues' },
];

const umaFase = computed(() => filtroFase.value !== '');

/* ---------------------------------------------------------------- prazos */

// Data LOCAL (toISOString e UTC: depois das 21h "hoje" viraria amanha).
const hoje = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

/*
 * "Em atraso" depende da fase:
 *  - antes da ativacao: parado ha mais de 7 dias (o mesmo aviso vermelho de antes);
 *  - em execucao: vigencia ja terminou e o cronograma nao foi encerrado.
 * Entregues nunca estao em atraso.
 */
function emAtraso(fase, card) {
  if (fase === 'entregue') return false;
  if (fase === 'em_execucao') return !!card.dt_final && card.dt_final < hoje();
  return (card.dias_na_fase ?? 0) > 7;
}

const venceHoje = (card) => card.dt_final === hoje();

/* --------------------------------------------------------- indicadores */

const totalDa = (fase) => props.colunas.find(c => c.value === fase)?.total ?? 0;
const cardsDe = (fase) => props.colunas.find(c => c.value === fase)?.cards ?? [];

const atrasados = computed(() => props.colunas
  .reduce((n, col) => n + col.cards.filter(card => emAtraso(col.value, card)).length, 0));

const concluidosNoMes = computed(() => {
  const mes = hoje().slice(0, 7);
  return cardsDe('entregue').filter(c => (c.desde ?? '').slice(0, 7) === mes).length;
});

const emFluxo = computed(() => ['elaboracao', 'em_analise', 'aprovado'].reduce((n, f) => n + totalDa(f), 0));

const indicadores = computed(() => [
  {
    titulo: 'Em tramitação',
    valor: emFluxo.value,
    selo: `${totalDa('aprovado')} aguardando ativação`,
    icone: BoltIcon,
    icone_cor: 'bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300',
    selo_cor: 'bg-blue-50 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300',
  },
  {
    titulo: 'Em atraso',
    valor: atrasados.value,
    selo: atrasados.value ? 'Requer atenção' : 'Tudo em dia',
    icone: ClockIcon,
    icone_cor: 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-300',
    selo_cor: atrasados.value
      ? 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-300'
      : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
  },
  {
    titulo: 'Concluídos no mês',
    valor: concluidosNoMes.value,
    selo: `${totalDa('entregue')} entregues no total`,
    icone: CheckCircleIcon,
    icone_cor: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
    selo_cor: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
  },
  {
    titulo: 'Em execução',
    valor: totalDa('em_execucao'),
    selo: 'Viagens em andamento',
    icone: TruckIcon,
    icone_cor: 'bg-violet-50 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
    selo_cor: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
  },
]);

/* "Atualizado ha ..." -- conta desde a ultima carga do quadro. */
const carregadoEm = ref(Date.now());
const agora = ref(Date.now());
let relogio = null;
onMounted(() => { relogio = setInterval(() => { agora.value = Date.now(); }, 30000); });
onBeforeUnmount(() => clearInterval(relogio));

const atualizadoHa = computed(() => {
  const min = Math.floor((agora.value - carregadoEm.value) / 60000);
  if (min < 1) return 'há poucos segundos';
  return min === 1 ? 'há 1 minuto' : `há ${min} minutos`;
});

/* -------------------------------------------------------------- filtros */

// Recorte rapido do quadro todo (no navegador).
const RECORTES = [
  { value: 'todos', label: 'Todos' },
  { value: 'atraso', label: 'Em atraso' },
  { value: 'hoje', label: 'Vencem hoje' },
];
const recorte = ref('todos');
const mostrarFiltros = ref(false);

/*
 * Filtros de CADA fase, no navegador: agem so sobre os cards da coluna, e
 * cada coluna guarda o seu estado. Fase e "Minha fila" consultam o servidor.
 */
const vazio = () => ({
  busca: '', ata: '', lote: '', municipio: '', prestador: '',
  caminhoes: '', parado: '', vigenciaDe: '', vigenciaAte: '',
});

const filtrosPorFase = reactive({});

function filtrosDa(fase) {
  if (!filtrosPorFase[fase]) filtrosPorFase[fase] = vazio();
  return filtrosPorFase[fase];
}

// Selects com valor exato: as opcoes saem dos proprios cards da coluna.
const CAMPOS_SELECT = [
  { chave: 'ata', campo: 'ata', rotulo: 'Ata' },
  { chave: 'lote', campo: 'lote', rotulo: 'Lote' },
  { chave: 'municipio', campo: 'municipio', rotulo: 'Município' },
  { chave: 'prestador', campo: 'prestador', rotulo: 'Prestador' },
];

function opcoesDaColuna(col, campo) {
  return [...new Set(col.cards.map(c => c[campo]).filter(Boolean))]
    .sort((a, b) => String(a).localeCompare(String(b), 'pt-BR', { numeric: true }));
}

function totalFiltrosAtivos(fase) {
  const { busca, ...demais } = filtrosDa(fase);
  return Object.values(demais).filter(v => v !== '').length + (busca.trim() ? 1 : 0);
}

const totalFiltrosGeral = computed(() => props.colunas.reduce((n, c) => n + totalFiltrosAtivos(c.value), 0));

function limparColuna(fase) {
  filtrosPorFase[fase] = vazio();
}

const semAcento = (v) => String(v ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

function cardsVisiveis(col) {
  const f = filtrosDa(col.value);
  const termo = semAcento(f.busca).trim();

  return col.cards.filter((c) => {
    if (recorte.value === 'atraso' && !emAtraso(col.value, c)) return false;
    if (recorte.value === 'hoje' && !venceHoje(c)) return false;
    if (termo && ![c.numero, c.municipio, c.prestador, c.ata, c.lote].some(v => semAcento(v).includes(termo))) return false;
    if (f.ata && c.ata !== f.ata) return false;
    if (f.lote && c.lote !== f.lote) return false;
    if (f.municipio && c.municipio !== f.municipio) return false;
    if (f.prestador && c.prestador !== f.prestador) return false;
    if (f.caminhoes === 'com' && !(c.caminhoes > 0)) return false;
    if (f.caminhoes === 'sem' && c.caminhoes > 0) return false;
    if (f.parado && !((c.dias_na_fase ?? 0) > Number(f.parado))) return false;
    if (f.vigenciaDe && !(c.dt_final && c.dt_final >= f.vigenciaDe)) return false;
    if (f.vigenciaAte && !(c.dt_inicio && c.dt_inicio <= f.vigenciaAte)) return false;
    return true;
  });
}

/* ------------------------------------------------------------ servidor */

function parametros() {
  return {
    minha_fila: minhaFila.value ? 1 : undefined,
    fase: filtroFase.value || undefined,
  };
}

function aplicarFiltros() {
  router.get(route('tdap.acompanhamento.index'), parametros(), {
    preserveState: true,
    replace: true,
    onSuccess: () => { carregadoEm.value = Date.now(); agora.value = Date.now(); },
  });
}

function exibirFase(valor) {
  filtroFase.value = valor;
  aplicarFiltros();
}

function alternarMinhaFila() {
  minhaFila.value = !minhaFila.value;
  aplicarFiltros();
}

/* ------------------------------------------------------------ relatorio */

const menuRelatorio = ref(null);
const menuRelatorioAberto = ref(false);
const pdfAberto = ref(false);

function fecharMenuAoClicarFora(e) {
  if (menuRelatorio.value && !menuRelatorio.value.contains(e.target)) menuRelatorioAberto.value = false;
}
onMounted(() => document.addEventListener('mousedown', fecharMenuAoClicarFora));
onBeforeUnmount(() => document.removeEventListener('mousedown', fecharMenuAoClicarFora));

/* O que esta na tela, fase a fase: o PDF e o CSV saem do mesmo recorte. */
const fasesDoRelatorio = computed(() => props.colunas.map(col => ({
  value: col.value,
  ordem: col.ordem,
  label: col.label,
  total: col.total,
  linhas: cardsVisiveis(col).map(c => ({ ...c, atraso: emAtraso(col.value, c) })),
})));

// Texto dos recortes ativos, impresso no relatorio.
const resumoDosFiltros = computed(() => {
  const partes = [];
  if (minhaFila.value) partes.push('Minha fila');
  if (filtroFase.value) partes.push(`Fase: ${opcoesDeFase.find(o => o.value === filtroFase.value)?.label}`);
  if (recorte.value !== 'todos') partes.push(RECORTES.find(r => r.value === recorte.value)?.label);
  if (totalFiltrosGeral.value) partes.push(`${totalFiltrosGeral.value} filtro(s) por fase`);
  return partes.join(', ');
});

function abrirPdf() {
  menuRelatorioAberto.value = false;
  pdfAberto.value = true;
}

/*
 * CSV do que esta na tela (fases, recorte e filtros aplicados). BOM UTF-8 e
 * ";" para o Excel abrir com acento, como os demais exports do modulo.
 */
function gerarCsv() {
  menuRelatorioAberto.value = false;
  const cab = ['Fase', 'Cronograma', 'Município', 'Prestador', 'Ata', 'Lote', 'Caminhões', 'Dias na fase', 'Em atraso', 'Início', 'Fim'];
  const linhas = props.colunas.flatMap(col => cardsVisiveis(col).map(c => [
    col.label, c.numero, c.municipio, c.prestador, c.ata, c.lote, c.caminhoes,
    c.dias_na_fase ?? '', emAtraso(col.value, c) ? 'Sim' : 'Não', c.dt_inicio ?? '', c.dt_final ?? '',
  ]));

  const csv = [cab, ...linhas]
    .map(l => l.map(v => `"${String(v ?? '').replace(/"/g, '""')}"`).join(';'))
    .join('\r\n');

  const url = URL.createObjectURL(new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8' }));
  const a = Object.assign(document.createElement('a'), {
    href: url,
    download: `acompanhamento_cronogramas_${hoje()}.csv`,
  });
  a.click();
  URL.revokeObjectURL(url);
}

/* ---------------------------------------------------------------- card */

const selecionado = ref(null);

function abrir(card) {
  selecionado.value = card;
}

// Depois de tramitar, o card muda de coluna: recarrega so o quadro.
function recarregarQuadro() {
  router.reload({
    only: ['colunas'],
    preserveScroll: true,
    onSuccess: () => { carregadoEm.value = Date.now(); agora.value = Date.now(); },
  });
}

function fmtData(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}
</script>
