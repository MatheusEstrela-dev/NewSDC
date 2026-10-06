<template>
  <div class="flex flex-col sm:flex-row items-center gap-6 flex-1 justify-center">
    <!-- Donut -->
    <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex-shrink-0 flex items-center justify-center group/donut">
      <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
        <!-- Background Circle -->
        <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800/50" fill="none" />

        <!-- Segments -->
        <circle
          v-for="(segment, index) in segmentos"
          :key="segment.name"
          cx="50"
          cy="50"
          r="40"
          fill="none"
          stroke-width="8"
          stroke-linecap="round"
          :stroke="segment.color"
          :stroke-dasharray="segment.dashArray"
          :stroke-dashoffset="segment.dashOffset"
          class="transition-colors duration-300 ease-out cursor-pointer origin-center hover:opacity-100"
          :class="[
            destacado === index ? 'scale-110 opacity-100 brightness-110 drop-shadow-[0_0_8px_rgba(0,0,0,0.5)]' : 'scale-100 opacity-90 hover:scale-105'
          ]"
          @mouseenter="destacado = index"
          @mouseleave="destacado = null"
        />
      </svg>

      <!-- Center Info -->
      <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
        <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-white dark:bg-slate-900 shadow-[inset_0_2px_10px_rgba(0,0,0,0.1)] flex flex-col items-center justify-center z-10 transition-transform duration-300"
             :class="{'scale-105': destacado !== null}">
          <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100 transition-colors duration-300"
                :style="{ color: destacado !== null ? itens[destacado].color : '' }">
            {{ formatar(destacado !== null ? itens[destacado].value : total) }}
          </span>
          <span class="px-2 text-center text-[10px] font-medium leading-tight text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            {{ destacado !== null ? itens[destacado].name : rotuloTotal }}
          </span>
        </div>
      </div>
    </div>
    <!-- Legenda -->
    <div class="flex-1 space-y-3 w-full">
      <div
        v-for="(item, index) in itens"
        :key="item.name"
        class="flex items-center justify-between gap-3 group cursor-pointer transition-colors duration-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 rounded-lg"
        :class="{'bg-slate-50 dark:bg-slate-800/50 scale-[1.02] shadow-sm ring-1 ring-slate-100 dark:ring-slate-700': destacado === index}"
        :title="item.title"
        @mouseenter="destacado = index"
        @mouseleave="destacado = null"
      >
        <div class="flex items-center gap-2.5 min-w-0">
          <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
          <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ item.name }}</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ formatar(item.value) }}</span>
          <span class="text-xs text-slate-500 dark:text-slate-400 w-10 text-right">{{ item.percent }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * Donut de distribuicao da Visao Geral (SVG, sem biblioteca): segmento e
 * legenda se destacam juntos no hover, e o centro troca o total pelo valor do
 * segmento. Extraido de DonutChartWidget para o dashboard do TDAP usar o mesmo
 * desenho em vez de um segundo grafico de pizza.
 *
 * Cada item: { name, value, percent (0-100), color, title? }. Os percentuais
 * vem prontos de quem chama -- e quem sabe fecha-los em 100.
 */
import { computed, ref } from 'vue';

const props = defineProps({
  itens: { type: Array, default: () => [] },
  rotuloTotal: { type: String, default: 'Total' },
});

const destacado = ref(null);

const total = computed(() => props.itens.reduce((soma, item) => soma + item.value, 0));

const formatar = (valor) => Number(valor).toLocaleString('pt-BR');

const segmentos = computed(() => {
  const raio = 40;
  const circunferencia = 2 * Math.PI * raio;
  let acumulado = 0;

  return props.itens.map((item) => {
    const fracao = item.percent / 100;
    const segmento = {
      ...item,
      dashArray: `${fracao * circunferencia} ${circunferencia}`,
      dashOffset: -acumulado * circunferencia,
    };
    acumulado += fracao;
    return segmento;
  });
});
</script>
