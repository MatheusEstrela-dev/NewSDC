<template>
  <TdapPainel rotulo="Evolução" titulo="Capacitações por mês" subtitulo="Participações por situação, capacitações no mês e a média mensal. Clique num mês para detalhar">
    <template #acoes>
      <div class="flex items-end gap-2">
        <div class="w-24">
          <SeletorCompacto rotulo="Ano" ocultar-rotulo :model-value="valor('ano')" :opcoes="opcoesAno" @update:model-value="$emit('alterar', { chave: 'ano', valor: $event })" />
        </div>
        <div class="w-32">
          <SeletorCompacto rotulo="Tipo" ocultar-rotulo :model-value="valor('tipo')" :opcoes="opcoesTipo" @update:model-value="$emit('alterar', { chave: 'tipo', valor: $event })" />
        </div>
      </div>
    </template>

    <ListEmptyState
      v-if="semDados"
      :icon="ClockIcon"
      title="Sem participantes no período"
      helper="Nenhuma participação com referência nos meses exibidos."
    />
    <template v-else>
      <ul class="flex flex-wrap gap-2 px-5 pt-4">
        <li v-for="serie in SITUACOES" :key="serie.chave" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: serie.cor }"></span>
          {{ serie.nome }}
        </li>
        <li class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <span class="w-3 border-t-2 border-dashed" :style="{ borderColor: COR_CAPACITACOES }"></span>
          Capacitações (eixo direito)
        </li>
        <li class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <span class="w-3 border-t-2 border-dotted" :style="{ borderColor: tema.corTexto }"></span>
          Média de concluídos: {{ formatarDecimal(mediaConcluidos) }}/mês
        </li>
      </ul>
      <div class="h-80 min-w-0 cursor-pointer px-3 py-3">
        <LazyChart type="line" height="100%" :options="opcoesGrafico" :series="series" />
      </div>

      <DetalheMesBloco v-if="mesSelecionado" :mes="mesSelecionado" :detalhe="detalheDoMes" @fechar="$emit('selecionar-mes', null)" />
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Grafico combinado por mes (props.porMes: 12 meses, vazios com 0):
 * - colunas agrupadas por situacao (concluidos, em andamento, desistentes),
 *   no eixo esquerdo (pessoas);
 * - linha tracejada de capacitacoes (turmas distintas no mes), no eixo direito;
 * - linha de referencia na media mensal de concluidos (meses com participacao).
 * Ano e tipo ficam no cabecalho do painel. Clicar num mes emite
 * `selecionar-mes`; a Index pede a prop lazy detalheMes e devolve aqui.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DetalheMesBloco from '@/Components/Organisms/Treinamento/Indicadores/DetalheMesBloco.vue';
import SeletorCompacto from '@/Components/Organisms/Treinamento/Indicadores/SeletorCompacto.vue';
import {
  COR_CAPACITACOES, SITUACOES, formatarDecimal, formatarNumero, rotuloMes, useTemaGrafico,
} from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porMes: { type: Array, default: () => [] },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
  /** 'YYYY-MM' clicado, ou null. */
  mesSelecionado: { type: String, default: null },
  /** Prop lazy detalheMes; so vale quando e do mes selecionado. */
  detalheMes: { type: Object, default: null },
});

const emit = defineEmits(['alterar', 'selecionar-mes']);

const { tema } = useTemaGrafico();

const valor = (chave) => (props.filtros[chave] ? String(props.filtros[chave]) : '');

const opcoesAno = computed(() => [{ value: '', label: 'Todos os anos' }, ...(props.opcoes.anos ?? []).map((a) => ({ value: String(a), label: String(a) }))]);
const opcoesTipo = computed(() => [{ value: '', label: 'Todos os tipos' }, ...(props.opcoes.tipos ?? []).map((t) => ({ value: t.value, label: t.label }))]);

const semDados = computed(() => props.porMes.every((m) => !m.participantes));

// Detalhe de outro mes (ou de antes do filtro mudar) nao aparece: o bloco
// mostra o esqueleto ate a resposta certa chegar.
const detalheDoMes = computed(() => (props.detalheMes && props.detalheMes.mes === props.mesSelecionado ? props.detalheMes : null));

const SERIE_CAPACITACOES = 'Capacitações';

const series = computed(() => [
  ...SITUACOES.map((s) => ({ name: s.nome, type: 'column', data: props.porMes.map((m) => Number(m[s.chave] ?? 0)) })),
  { name: SERIE_CAPACITACOES, type: 'line', data: props.porMes.map((m) => Number(m.capacitacoes ?? 0)) },
]);

// Media so dos meses com participacao: mes vazio da janela puxaria a linha para baixo.
const mediaConcluidos = computed(() => {
  const meses = props.porMes.filter((m) => Number(m.participantes ?? 0) > 0);
  return meses.length ? meses.reduce((soma, m) => soma + Number(m.concluidos ?? 0), 0) / meses.length : 0;
});

// Clicar no mes aberto fecha o detalhe.
function aoClicarMes(_evento, _grafico, { dataPointIndex }) {
  const mes = props.porMes[dataPointIndex]?.mes ?? null;
  emit('selecionar-mes', mes && mes !== props.mesSelecionado ? mes : null);
}

const opcoesGrafico = computed(() => {
  const texto = { style: { colors: tema.value.corTexto, fontSize: '11px' } };
  // Titulo de eixo no Apex usa style.color (singular), diferente dos labels.
  const tituloEixo = (text) => ({ text, style: { color: tema.value.corTexto, fontSize: '11px', fontWeight: 500 } });
  const inteiro = (v) => formatarNumero(Math.round(v));
  const situacoes = SITUACOES.map((s) => s.nome);

  return {
    chart: {
      type: 'line',
      toolbar: { show: false },
      background: 'transparent',
      fontFamily: 'inherit',
      events: { dataPointSelection: aoClicarMes },
    },
    colors: [...SITUACOES.map((s) => s.cor), COR_CAPACITACOES],
    // Colunas sem contorno; so a linha de capacitacoes tem traco (tracejado).
    stroke: { width: [...situacoes.map(() => 0), 3], dashArray: [...situacoes.map(() => 0), 6], curve: 'smooth' },
    markers: { size: [...situacoes.map(() => 0), 4], strokeWidth: 0, hover: { sizeOffset: 2 } },
    plotOptions: { bar: { borderRadius: 3, columnWidth: '70%' } },
    dataLabels: { enabled: false },
    // A coluna clicada nao fica "presa" escurecida: a selecao visivel e o bloco de detalhe.
    states: { active: { filter: { type: 'none' } } },
    grid: { borderColor: tema.value.corGrade },
    xaxis: {
      categories: props.porMes.map((m) => rotuloMes(m.mes)),
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: { rotate: -45, hideOverlappingLabels: true, ...texto },
    },
    yaxis: [
      { seriesName: situacoes, min: 0, forceNiceScale: true, title: tituloEixo('Pessoas'), labels: { ...texto, formatter: inteiro } },
      { seriesName: SERIE_CAPACITACOES, opposite: true, min: 0, forceNiceScale: true, title: tituloEixo('Capacitações'), labels: { ...texto, formatter: inteiro } },
    ],
    annotations: {
      yaxis: mediaConcluidos.value > 0 ? [{
        y: mediaConcluidos.value,
        yAxisIndex: 0,
        borderColor: tema.value.corTexto,
        strokeDashArray: 3,
        label: {
          text: `Média ${formatarDecimal(mediaConcluidos.value)}`,
          position: 'left',
          textAnchor: 'start',
          borderWidth: 0,
          style: { background: 'transparent', color: tema.value.corTexto, fontSize: '10px' },
        },
      }] : [],
    },
    tooltip: { theme: tema.value.tooltip, shared: true, intersect: false, y: { formatter: (v) => formatarNumero(v) } },
    legend: { show: false },
  };
});
</script>
