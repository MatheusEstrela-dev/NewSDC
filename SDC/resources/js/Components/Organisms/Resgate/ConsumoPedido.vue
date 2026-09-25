<template>
  <section v-if="consumos.length" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" data-consumo-pedido>
    <h2 class="text-base font-bold text-slate-900 dark:text-white">De onde saíram os pontos · {{ numero(total) }}</h2>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lançamentos do placar consumidos pelo débito, dos mais antigos para os mais novos.</p>
    <div class="mt-3 overflow-x-auto">
      <table class="w-full text-xs">
        <thead class="text-left text-slate-500 dark:text-slate-400">
          <tr><th class="py-1 pr-3">Lançamento</th><th class="py-1 pr-3">Competência</th><th class="py-1 pr-3">Módulo</th><th class="py-1 pr-3 text-right">Pontos</th><th class="py-1">Origem</th></tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
          <tr v-for="consumo in consumos" :key="consumo.lancamento_id" class="text-slate-700 dark:text-slate-200">
            <td class="py-1 pr-3 tabular-nums">#{{ consumo.lancamento_id }}</td>
            <td class="py-1 pr-3">{{ data(consumo.competencia_em) }}</td>
            <td class="py-1 pr-3 uppercase">{{ consumo.modulo }}</td>
            <td class="py-1 pr-3 text-right tabular-nums">{{ numero(consumo.pontos) }}</td>
            <td class="max-w-[16rem] truncate py-1 font-mono text-[10px] text-slate-500" :title="consumo.chave_canonica">{{ consumo.chave_canonica }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
/**
 * Rastreio do ponto consumido (plano, secao 2.3): cada debito aponta os
 * lancamentos do ledger que o compuseram, ate a evidencia de origem.
 */
import { computed } from 'vue';

const props = defineProps({
  consumos: { type: Array, default: () => [] },
});

const total = computed(() => props.consumos.reduce((soma, c) => soma + Number(c.pontos), 0));
const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const data = (valor) => (valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(String(valor).replace(' ', 'T').replace(/\+00$/, 'Z'))) : '');
</script>
