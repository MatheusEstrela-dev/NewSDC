<template>
  <TdapPainel rotulo="Estratégico" titulo="Indicadores estratégicos" subtitulo="Densidade e porte médio das capacitações">
    <dl class="grid grid-cols-1 divide-y divide-slate-200 dark:divide-slate-700/40 sm:grid-cols-2 sm:divide-x sm:divide-y-0">
      <div v-for="indicador in indicadores" :key="indicador.rotulo" class="min-w-0 px-5 py-4">
        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ indicador.rotulo }}</dt>
        <dd class="mt-1 text-2xl font-semibold text-blue-700 dark:text-blue-300">{{ indicador.valor }}</dd>
        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ indicador.ajuda }}</p>
      </div>
    </dl>
  </TdapPainel>
</template>

<script setup>
/**
 * Dois indicadores calculados pelo servidor (props.kpis); os percentuais de
 * cobertura ficam no painel de cobertura territorial. Quando nao ha
 * populacao cadastrada, capacitados_por_100k vem null e aparece como traco:
 * zero seria um numero falso.
 */
import { computed } from 'vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { formatarDecimal } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  kpis: { type: Object, required: true },
});

const indicadores = computed(() => [
  {
    rotulo: 'Capacitados por 100 mil habitantes',
    valor: props.kpis.capacitados_por_100k === null || props.kpis.capacitados_por_100k === undefined
      ? '—'
      : formatarDecimal(props.kpis.capacitados_por_100k, 1),
    ajuda: 'Sobre a população dos municípios do recorte',
  },
  {
    rotulo: 'Média de participantes por capacitação',
    valor: formatarDecimal(props.kpis.media_participantes_por_capacitacao, 1),
    ajuda: 'Capacitados divididos pelas capacitações realizadas',
  },
]);
</script>
