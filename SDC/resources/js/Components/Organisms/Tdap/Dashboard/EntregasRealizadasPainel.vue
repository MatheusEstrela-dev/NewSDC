<template>
  <TdapPainel
    rotulo="Desempenho operacional"
    titulo="Entregas realizadas"
    subtitulo="Viagens aprovadas no período, pela data em que foram feitas"
  >
    <template #acoes>
      <div class="flex items-center gap-1 rounded-lg border border-slate-200 p-1 dark:border-slate-700/50" role="group" aria-label="Período">
        <button
          v-for="opcao in entregas.opcoes"
          :key="opcao.value"
          type="button"
          class="rounded-md px-2.5 py-1 text-xs font-medium transition-colors"
          :class="opcao.value === entregas.periodo ? 'bg-blue-600 text-white shadow-sm'
            : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200'"
          :aria-pressed="opcao.value === entregas.periodo"
          :disabled="carregando"
          @click="$emit('update:periodo', opcao.value)"
        >
          {{ opcao.label }}
        </button>
      </div>
    </template>

    <div class="px-5 pt-4" :class="{ 'opacity-60 transition-opacity': carregando }">
      <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <span class="text-3xl font-bold text-slate-900 dark:text-slate-100">{{ entregas.total.toLocaleString('pt-BR') }}</span>
        <span class="text-sm text-slate-500 dark:text-slate-400">{{ entregas.total === 1 ? 'viagem realizada' : 'viagens realizadas' }}</span>
        <span
          v-if="entregas.variacao !== null"
          class="rounded-md px-1.5 py-0.5 text-xs font-semibold"
          :class="entregas.variacao >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
            : 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'"
        >
          {{ entregas.variacao >= 0 ? '↗' : '↘' }} {{ variacaoFormatada }}
        </span>
        <span class="text-xs text-slate-400 dark:text-slate-500">
          {{ entregas.variacao !== null ? 'comparado ao período anterior' : 'sem viagens no período anterior para comparar' }}
        </span>
      </div>

      <div class="h-64">
        <LazyChart type="area" height="100%" :options="opcoesGrafico" :series="series" />
      </div>
    </div>
  </TdapPainel>
</template>

<script setup>
/**
 * Serie de viagens entregues por dia/mes (TdapDashboardService::entregas).
 * O painel so desenha: a troca de periodo sobe para a pagina, que recarrega a
 * prop `entregas` do servidor.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { useTheme } from '@/Composables/ui/useTheme';
import { dataDeISO } from '@/Support/dataLocal';

const props = defineProps({
  entregas: { type: Object, required: true },
  carregando: { type: Boolean, default: false },
});

defineEmits(['update:periodo']);

const { isDarkMode } = useTheme();

const variacaoFormatada = computed(() => `${Math.abs(props.entregas.variacao).toLocaleString('pt-BR', { maximumFractionDigits: 1 })}%`);

const FORMATO_ROTULO = {
  day: { day: '2-digit', month: '2-digit' },
  month: { month: 'short' },
};

const categorias = computed(() => props.entregas.serie.map(({ data }) => {
  const rotulo = dataDeISO(data).toLocaleDateString('pt-BR', FORMATO_ROTULO[props.entregas.granularidade]);
  return rotulo.replace('.', '').replace(/^./, (c) => c.toUpperCase());
}));

const series = computed(() => [{ name: 'Viagens', data: props.entregas.serie.map(({ total }) => total) }]);

const opcoesGrafico = computed(() => {
  const corTexto = isDarkMode.value ? '#64748b' : '#94a3b8';

  return {
    chart: { type: 'area', toolbar: { show: false }, zoom: { enabled: false }, background: 'transparent', fontFamily: 'inherit' },
    colors: ['#3b82f6'],
    stroke: { curve: 'straight', width: 2 },
    markers: { size: 4, strokeWidth: 2, strokeColors: '#3b82f6', colors: isDarkMode.value ? '#0f172a' : '#ffffff', hover: { size: 6 } },
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 100] } },
    dataLabels: { enabled: false },
    grid: { borderColor: isDarkMode.value ? '#1e293b' : '#e2e8f0', strokeDashArray: 0 },
    xaxis: {
      categories: categorias.value,
      axisBorder: { show: false },
      axisTicks: { show: false },
      tickAmount: Math.min(categorias.value.length, 10),
      labels: { rotate: 0, hideOverlappingLabels: true, style: { colors: corTexto, fontSize: '11px' } },
      tooltip: { enabled: false },
    },
    yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: corTexto, fontSize: '11px' }, formatter: (v) => Math.round(v).toLocaleString('pt-BR') } },
    tooltip: { theme: isDarkMode.value ? 'dark' : 'light', y: { formatter: (v) => `${v.toLocaleString('pt-BR')} ${v === 1 ? 'viagem' : 'viagens'}` } },
    legend: { show: false },
  };
});
</script>
