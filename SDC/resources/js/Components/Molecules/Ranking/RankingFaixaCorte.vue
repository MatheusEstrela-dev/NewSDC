<template>
  <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
    <RankingFaixaBadge :faixa="faixa" size="sm" />
    <span class="font-semibold">{{ descricao }}</span>
  </div>
</template>

<script setup>
/**
 * Cabecalho de grupo da classificacao: marca onde comeca uma faixa e mostra o
 * corte (pontuacao minima). Os limiares chegam do controller
 * (config('ranking.faixas')); o fallback local repete FaixaRanking.
 */
import { computed } from 'vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';

const LIMIARES_PADRAO = {
  bronze: { min: 0, max: 299 },
  prata: { min: 300, max: 699 },
  ouro: { min: 700, max: 1499 },
  diamante: { min: 1500, max: null },
};

const props = defineProps({
  faixa: { type: String, default: null },
  limiares: { type: Object, default: null },
});

const formatador = new Intl.NumberFormat('pt-BR');

const descricao = computed(() => {
  const limiar = (props.limiares ?? LIMIARES_PADRAO)[props.faixa] ?? LIMIARES_PADRAO[props.faixa];
  if (!limiar) return 'Faixa em apuração';
  const min = Number(limiar.min ?? 0);
  if (limiar.max === null || limiar.max === undefined) return `A partir de ${formatador.format(min)} pontos`;
  if (min === 0) return `Até ${formatador.format(Number(limiar.max))} pontos`;
  return `De ${formatador.format(min)} a ${formatador.format(Number(limiar.max))} pontos`;
});
</script>
