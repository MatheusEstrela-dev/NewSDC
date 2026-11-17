<template>
  <TdapPainel rotulo="Evolução" titulo="Capacitações por ano" subtitulo="Pelo ano de início da turma">
    <ListEmptyState
      v-if="porAno.length === 0"
      :icon="BookOpenIcon"
      title="Sem capacitações realizadas"
      helper="Nenhuma turma com participante capacitado no recorte."
    />
    <div v-else class="h-72 min-w-0 px-3 py-3">
      <LazyChart type="bar" height="100%" :options="opcoes" :series="series" />
    </div>
  </TdapPainel>
</template>

<script setup>
/** Colunas de capacitacoes por ano (props.porAno, crescente). */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import { COR_CAPACITACOES, formatarNumero, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porAno: { type: Array, default: () => [] },
});

const { tema } = useTemaGrafico();

const series = computed(() => [{ name: 'Capacitações', data: props.porAno.map((a) => a.capacitacoes) }]);

const opcoes = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
  colors: [COR_CAPACITACOES],
  plotOptions: { bar: { borderRadius: 4, columnWidth: '45%' } },
  dataLabels: { enabled: false },
  grid: { borderColor: tema.value.corGrade },
  xaxis: {
    categories: props.porAno.map((a) => String(a.ano)),
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: tema.value.corTexto, fontSize: '11px' } },
  },
  yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: tema.value.corTexto, fontSize: '11px' }, formatter: (v) => formatarNumero(Math.round(v)) } },
  tooltip: {
    theme: tema.value.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) => {
        const participantes = props.porAno[dataPointIndex]?.participantes ?? 0;
        return `${formatarNumero(v)} capacitações, ${formatarNumero(participantes)} participantes`;
      },
    },
  },
  legend: { show: false },
}));
</script>
