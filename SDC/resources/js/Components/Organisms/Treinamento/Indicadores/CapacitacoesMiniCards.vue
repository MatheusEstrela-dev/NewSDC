<template>
  <section aria-label="Capacitações com mais participantes">
    <p v-if="cards.length === 0" class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">
      Nenhuma capacitação com participante no recorte.
    </p>

    <!--
      Celular: carrossel DENTRO do bloco (overflow-x-auto + snap), a pagina nao
      rola de lado. A partir de xl vira pilha na coluna do destaque.
    -->
    <ul v-else class="flex min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto pb-2 xl:flex-col xl:overflow-visible xl:pb-0">
      <li v-for="card in cards" :key="card.id" class="w-[78%] shrink-0 snap-start sm:w-64 xl:w-full">
        <button
          type="button"
          class="block w-full rounded-xl border border-slate-200 bg-white p-4 text-left shadow-sm transition-colors hover:border-orange-400 dark:border-slate-700/40 dark:bg-slate-900/40 dark:hover:border-orange-500/60"
          :title="`Filtrar por ${card.titulo}`"
          @click="$emit('selecionar', String(card.id))"
        >
          <p class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ card.titulo }}</p>
          <div class="mt-1 flex items-end justify-between gap-3">
            <p class="text-3xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(card.participantes) }}</p>
            <div class="h-12 w-28 overflow-hidden">
              <LazyChart class="!min-h-0" type="area" height="48" :options="opcoesSparkline" :series="[{ name: 'Participantes', data: card.serie }]" />
            </div>
          </div>
          <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">participantes · {{ periodo }}</p>
        </button>
      </li>
    </ul>
  </section>
</template>

<script setup>
/**
 * Mini card por capacitacao (top de props.porCapacitacao): participantes e uma
 * sparkline com a serie mensal da mesma janela de porMes. Clicar filtra o
 * painel por aquela capacitacao.
 */
import { computed } from 'vue';
import LazyChart from '@/Components/Common/LazyChart.vue';
import { COR_CAPACITACOES, formatarNumero, rotuloMes, useTemaGrafico } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porCapacitacao: { type: Array, default: () => [] },
  porMes: { type: Array, default: () => [] },
  limite: { type: Number, default: 6 },
});

defineEmits(['selecionar']);

const { tema } = useTemaGrafico();

const cards = computed(() => props.porCapacitacao.slice(0, props.limite).map((c) => ({
  id: c.treinamento_id,
  titulo: c.titulo,
  participantes: c.participantes,
  serie: c.serie ?? [],
})));

const periodo = computed(() => (props.porMes.length
  ? `${rotuloMes(props.porMes[0].mes)} a ${rotuloMes(props.porMes[props.porMes.length - 1].mes)}`
  : 'últimos 12 meses'));

const opcoesSparkline = computed(() => ({
  chart: { type: 'area', sparkline: { enabled: true }, background: 'transparent', animations: { enabled: false } },
  colors: [COR_CAPACITACOES],
  stroke: { curve: 'smooth', width: 2 },
  fill: { type: 'gradient', gradient: { opacityFrom: 0.4, opacityTo: 0.02 } },
  tooltip: { theme: tema.value.tooltip, fixed: { enabled: false }, x: { show: false }, y: { formatter: (v) => formatarNumero(v) } },
}));
</script>
