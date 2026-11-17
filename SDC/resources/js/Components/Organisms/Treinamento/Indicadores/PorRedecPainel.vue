<template>
  <TdapPainel rotulo="Território" titulo="Participantes por REDEC" subtitulo="Capacitados por região de defesa civil">
    <ListEmptyState
      v-if="porRedec.length === 0"
      :icon="BuildingIcon"
      title="Nenhuma REDEC no recorte"
      helper="Os capacitados do recorte não estão ligados a uma REDEC."
    />
    <div v-else class="min-w-0 px-3 py-3" :style="{ height: `${altura}px` }">
      <LazyChart type="bar" height="100%" :options="opcoes" :series="series" />
    </div>
  </TdapPainel>
</template>

<script setup>
/** Barras horizontais de participantes por REDEC (props.porRedec, ja em ordem desc). */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import { COR_INSTITUCIONAL, encurtar, formatarNumero, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porRedec: { type: Array, default: () => [] },
});

const { tema } = useTemaGrafico();

const altura = computed(() => Math.max(260, props.porRedec.length * 34 + 40));

const series = computed(() => [{ name: 'Participantes', data: props.porRedec.map((r) => r.participantes) }]);

const opcoes = computed(() => ({
  chart: { type: 'bar', toolbar: { show: false }, background: 'transparent', fontFamily: 'inherit' },
  colors: [COR_INSTITUCIONAL],
  plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '65%' } },
  dataLabels: { enabled: false },
  grid: { borderColor: tema.value.corGrade, xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
  xaxis: {
    categories: props.porRedec.map((r) => r.nome),
    labels: { style: { colors: tema.value.corTexto, fontSize: '11px' }, formatter: (v) => formatarNumero(Math.round(v)) },
    axisBorder: { show: false },
  },
  yaxis: { labels: { maxWidth: 130, style: { colors: tema.value.corTexto, fontSize: '11px' }, formatter: (v) => encurtar(v, 20) } },
  tooltip: {
    theme: tema.value.tooltip,
    y: {
      formatter: (v, { dataPointIndex }) => {
        const capacitacoes = props.porRedec[dataPointIndex]?.capacitacoes ?? 0;
        return `${formatarNumero(v)} participantes em ${formatarNumero(capacitacoes)} capacitações`;
      },
    },
  },
  legend: { show: false },
}));
</script>
