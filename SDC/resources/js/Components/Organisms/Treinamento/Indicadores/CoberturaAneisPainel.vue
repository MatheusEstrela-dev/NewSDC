<template>
  <TdapPainel rotulo="Cobertura" titulo="Alcance" subtitulo="Percentual sobre o total do estado">
    <ul class="grid grid-cols-3 gap-1 px-2 py-4">
      <li v-for="anel in aneis" :key="anel.rotulo" class="flex min-w-0 flex-col items-center">
        <div class="h-[120px] w-full min-w-0 overflow-hidden">
          <LazyChart class="!min-h-0" type="radialBar" height="120" :options="opcoesAnel(anel)" :series="[anel.valor]" />
        </div>
        <p class="mt-1 text-center text-[11px] font-medium leading-tight text-slate-600 dark:text-slate-300">{{ anel.rotulo }}</p>
      </li>
    </ul>
  </TdapPainel>
</template>

<script setup>
/** Tres aneis de percentual (props.kpis): municipios de MG, REDECs e participacao feminina. */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { COR_FEMININO, COR_INSTITUCIONAL, formatarDecimal, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  kpis: { type: Object, required: true },
});

const { tema } = useTemaGrafico();

const aneis = computed(() => [
  { rotulo: 'Municípios de MG', valor: Number(props.kpis.pct_municipios_mg ?? 0), cor: COR_INSTITUCIONAL },
  { rotulo: 'REDECs', valor: Number(props.kpis.pct_redecs ?? 0), cor: '#0891b2' },
  { rotulo: 'Participação feminina', valor: Number(props.kpis.participacao_feminina_pct ?? 0), cor: COR_FEMININO },
]);

const opcoesAnel = (anel) => ({
  chart: { type: 'radialBar', sparkline: { enabled: true }, background: 'transparent', fontFamily: 'inherit' },
  colors: [anel.cor],
  plotOptions: {
    radialBar: {
      hollow: { size: '58%' },
      track: { background: tema.value.corGrade },
      dataLabels: {
        name: { show: false },
        value: { offsetY: 6, fontSize: '15px', fontWeight: 600, color: tema.value.corTexto, formatter: (v) => `${formatarDecimal(v)}%` },
      },
    },
  },
  stroke: { lineCap: 'round' },
});
</script>
