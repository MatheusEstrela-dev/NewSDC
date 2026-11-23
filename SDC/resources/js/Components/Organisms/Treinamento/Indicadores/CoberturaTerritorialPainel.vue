<template>
  <TdapPainel rotulo="Território" titulo="Cobertura territorial" subtitulo="Municípios e REDECs com pelo menos uma participação no recorte">
    <div class="grid grid-cols-1 divide-y divide-slate-200 dark:divide-slate-700/40 lg:grid-cols-3 lg:divide-x lg:divide-y-0">
      <dl class="grid min-w-0 grid-cols-1 gap-5 px-5 py-5 sm:grid-cols-2 lg:grid-cols-1">
        <div v-for="item in numeros" :key="item.rotulo" class="min-w-0">
          <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ item.rotulo }}</dt>
          <dd class="mt-1">
            <span class="text-4xl font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(item.atendidos) }}</span>
            <span class="ml-1 text-sm text-slate-500 dark:text-slate-400">de {{ formatarNumero(item.total) }}</span>
          </dd>
          <dd class="mt-1 text-sm font-semibold text-blue-700 dark:text-blue-300">{{ formatarDecimal(item.pct) }}% {{ item.sufixo }}</dd>
        </div>
      </dl>

      <div class="min-w-0 py-4 lg:col-span-2">
        <div class="flex flex-wrap items-baseline justify-between gap-2 px-5">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Municípios atendidos por REDEC</p>
          <p class="text-[11px] text-slate-500 dark:text-slate-400">Clique numa REDEC para destacá-la no mapa</p>
        </div>

        <p v-if="porRedec.length === 0" class="px-5 py-4 text-sm text-slate-500 dark:text-slate-400">Nenhuma REDEC cadastrada.</p>
        <ul v-else class="mt-2 grid grid-cols-1 gap-x-4 px-3 md:grid-cols-2">
          <li v-for="redec in linhas" :key="redec.redec_id" class="min-w-0">
            <button
              type="button"
              class="block min-h-[40px] w-full rounded-lg px-2 py-1.5 text-left transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/60"
              :class="[
                redec.destacada ? 'bg-blue-50 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:ring-blue-500/30' : '',
                redecDestacada !== null && !redec.destacada ? 'opacity-60' : '',
              ]"
              :aria-pressed="redec.destacada"
              :title="redec.destacada ? 'Remover o destaque' : `Destacar ${redec.nome} no mapa`"
              @click="$emit('selecionar-redec', redec.redec_id)"
            >
              <span class="flex items-baseline justify-between gap-3">
                <span class="min-w-0 truncate text-xs font-medium text-slate-800 dark:text-slate-100">{{ redec.nome }}</span>
                <span class="shrink-0 text-xs tabular-nums text-slate-600 dark:text-slate-300">
                  <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatarNumero(redec.municipios_atendidos) }}</span>
                  de {{ formatarNumero(redec.municipios_total) }}
                </span>
              </span>
              <!-- Trilho = tamanho da REDEC (esmaecido); preenchimento = atendidos. -->
              <span class="mt-1 block h-2 rounded-full bg-transparent">
                <span class="block h-full overflow-hidden rounded-full bg-blue-100 dark:bg-blue-500/15" :style="{ width: `${redec.larguraTotal}%` }">
                  <span class="block h-full rounded-full bg-blue-600 dark:bg-blue-400" :style="{ width: `${redec.larguraAtendidos}%` }"></span>
                </span>
              </span>
            </button>
          </li>
        </ul>
      </div>
    </div>
  </TdapPainel>
</template>

<script setup>
/**
 * Cobertura territorial: municipios e REDECs atendidos sobre o total do estado
 * (props.kpis) e, por REDEC, municipios atendidos sobre os municipios dela
 * (props.porRedec, todas as REDECs). Clicar numa REDEC so emite
 * `selecionar-redec`: o destaque e do mapa, nao recarrega dado nenhum.
 */
import { computed } from 'vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { formatarDecimal, formatarNumero, percentual } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  kpis: { type: Object, required: true },
  porRedec: { type: Array, default: () => [] },
  redecDestacada: { type: Number, default: null },
});

defineEmits(['selecionar-redec']);

const numeros = computed(() => [
  {
    rotulo: 'Municípios atendidos',
    atendidos: props.kpis.municipios_atendidos,
    total: props.kpis.municipios_total,
    pct: props.kpis.pct_municipios_mg,
    sufixo: 'dos municípios de MG',
  },
  {
    rotulo: 'REDECs atendidas',
    atendidos: props.kpis.redecs_atendidas,
    total: props.kpis.redecs_total,
    pct: props.kpis.pct_redecs,
    sufixo: 'das REDECs',
  },
]);

// Barras na mesma escala: a REDEC com mais municipios ocupa a largura toda.
const maiorRedec = computed(() => Math.max(1, ...props.porRedec.map((r) => Number(r.municipios_total ?? 0))));

const linhas = computed(() => props.porRedec.map((r) => ({
  ...r,
  destacada: props.redecDestacada !== null && Number(r.redec_id) === Number(props.redecDestacada),
  larguraTotal: Math.max(2, percentual(Number(r.municipios_total ?? 0), maiorRedec.value)),
  larguraAtendidos: percentual(Number(r.municipios_atendidos ?? 0), Number(r.municipios_total ?? 0)),
})));
</script>
