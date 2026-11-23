<template>
  <TdapPainel rotulo="Evolução" titulo="Capacitações por ano" subtitulo="Turmas pelo ano de início e participantes pelo ano de referência">
    <ListEmptyState
      v-if="porAno.length === 0"
      :icon="BookOpenIcon"
      title="Sem capacitações realizadas"
      helper="Nenhuma turma com participante no recorte."
    />
    <template v-else>
      <ul class="flex flex-wrap gap-2 px-5 pt-4">
        <li v-for="serie in SERIES" :key="serie.chave" class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-200">
          <span class="h-2 w-2 rounded-full" :style="{ backgroundColor: serie.cor }"></span>
          {{ serie.nome }} ({{ serie.eixo }})
        </li>
      </ul>
      <div class="h-72 min-w-0 px-3 py-3">
        <LazyChart type="line" height="100%" :options="opcoesGrafico" :series="series" />
      </div>
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Linhas com marcadores por ano (props.porAno, crescente): capacitacoes (turmas)
 * no eixo esquerdo e participantes no direito. Escalas separadas porque as
 * grandezas sao de ordens diferentes; o rotulo em cada ponto mostra o valor.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import { COR_CAPACITACOES, COR_INSTITUCIONAL, formatarNumero, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porAno: { type: Array, default: () => [] },
});

const SERIES = [
  { chave: 'capacitacoes', nome: 'Capacitações', cor: COR_CAPACITACOES, eixo: 'eixo esquerdo' },
  { chave: 'participantes', nome: 'Participantes', cor: COR_INSTITUCIONAL, eixo: 'eixo direito' },
];

const { tema } = useTemaGrafico();

const series = computed(() => SERIES.map((s) => ({ name: s.nome, data: props.porAno.map((a) => Number(a[s.chave] ?? 0)) })));

const opcoesGrafico = computed(() => {
  const texto = { style: { colors: tema.value.corTexto, fontSize: '11px' } };
  const inteiro = (v) => formatarNumero(Math.round(v));
  const eixo = (titulo, opposite) => ({
    opposite,
    min: 0,
    forceNiceScale: true,
    title: { text: titulo, style: { color: tema.value.corTexto, fontSize: '11px', fontWeight: 500 } },
    labels: { ...texto, formatter: inteiro },
  });

  return {
    chart: { type: 'line', toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit', zoom: { enabled: false } },
    colors: SERIES.map((s) => s.cor),
    stroke: { width: 3, curve: 'straight' },
    markers: { size: 5, strokeWidth: 2, strokeColors: tema.value.corGrade, hover: { sizeOffset: 2 } },
    // Valor escrito em cada ponto: le o numero sem precisar do tooltip.
    dataLabels: {
      enabled: true,
      formatter: inteiro,
      offsetY: -6,
      background: { enabled: true, borderWidth: 0, borderRadius: 3, opacity: 0.85 },
      style: { fontSize: '10px', fontWeight: 600 },
    },
    grid: { borderColor: tema.value.corGrade, padding: { left: 8, right: 8 } },
    xaxis: {
      categories: props.porAno.map((a) => String(a.ano)),
      axisBorder: { show: false },
      axisTicks: { show: false },
      labels: texto,
    },
    yaxis: [eixo('Capacitações', false), eixo('Participantes', true)],
    tooltip: { theme: tema.value.tooltip, shared: true, intersect: false, y: { formatter: (v) => formatarNumero(v) } },
    legend: { show: false },
  };
});
</script>
