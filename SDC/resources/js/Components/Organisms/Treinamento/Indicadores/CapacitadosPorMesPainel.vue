<template>
  <TdapPainel rotulo="Evolução" titulo="Capacitados por mês" subtitulo="Pelo mês de conclusão do curso">
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
      helper="Nenhuma conclusão registrada nos meses exibidos."
    />
    <template v-else>
      <ul class="flex flex-wrap gap-2 px-5 pt-4">
        <li v-for="serie in SERIES" :key="serie.chave" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: serie.cor }"></span>
          {{ serie.nome }}
        </li>
      </ul>
      <div class="h-72 min-w-0 px-3 py-3">
        <LazyChart type="bar" height="100%" :options="opcoes" :series="series" />
      </div>
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Barras empilhadas por mes e sexo (props.porMes: 12 meses, vazios com 0).
 * Ano e tipo ficam no cabecalho do painel: sao os dois recortes que mais se
 * trocam olhando este grafico. A legenda e em chips proprios, nao a do Apex.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import SeletorCompacto from '@/Components/Organisms/Treinamento/Indicadores/SeletorCompacto.vue';
import { COR_FEMININO, COR_MASCULINO, COR_NAO_INFORMADO, formatarNumero, rotuloMes, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porMes: { type: Array, default: () => [] },
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
});

defineEmits(['alterar']);

const { tema } = useTemaGrafico();

const SERIES = [
  { chave: 'feminino', nome: 'Feminino', cor: COR_FEMININO },
  { chave: 'masculino', nome: 'Masculino', cor: COR_MASCULINO },
  { chave: 'nao_informado', nome: 'Não informado', cor: COR_NAO_INFORMADO },
];

const valor = (chave) => (props.filtros[chave] ? String(props.filtros[chave]) : '');

const opcoesAno = computed(() => [{ value: '', label: 'Todos os anos' }, ...(props.opcoes.anos ?? []).map((a) => ({ value: String(a), label: String(a) }))]);
const opcoesTipo = computed(() => [{ value: '', label: 'Todos os tipos' }, ...(props.opcoes.tipos ?? []).map((t) => ({ value: t.value, label: t.label }))]);

const semDados = computed(() => props.porMes.every((m) => !m.participantes));

const series = computed(() => SERIES.map((s) => ({ name: s.nome, data: props.porMes.map((m) => m[s.chave] ?? 0) })));

const opcoes = computed(() => ({
  chart: { type: 'bar', stacked: true, toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
  colors: SERIES.map((s) => s.cor),
  plotOptions: { bar: { borderRadius: 3, columnWidth: '55%' } },
  dataLabels: { enabled: false },
  grid: { borderColor: tema.value.corGrade },
  xaxis: {
    categories: props.porMes.map((m) => rotuloMes(m.mes)),
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { rotate: -45, hideOverlappingLabels: true, style: { colors: tema.value.corTexto, fontSize: '11px' } },
  },
  yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: tema.value.corTexto, fontSize: '11px' }, formatter: (v) => formatarNumero(Math.round(v)) } },
  tooltip: { theme: tema.value.tooltip, shared: true, intersect: false, y: { formatter: (v) => formatarNumero(v) } },
  legend: { show: false },
}));
</script>
