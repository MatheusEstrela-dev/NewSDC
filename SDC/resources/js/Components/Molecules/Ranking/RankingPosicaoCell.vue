<template>
  <div class="flex items-center gap-2">
    <template v-if="posicao === null">
      <span aria-hidden="true" class="text-slate-400 dark:text-slate-500">—</span>
      <span class="sr-only">sem classificação</span>
    </template>

    <RankingMedalha v-else-if="destacarTopo && posicao >= 1 && posicao <= 3" :posicao="posicao" compacta />

    <span v-else class="tabular-nums font-medium text-slate-600 dark:text-slate-300">
      {{ posicao }}º
    </span>
  </div>
</template>

<script setup>
/**
 * Celula de posicao da tabela do placar.
 *
 * O componente NAO calcula posicao: exibe exatamente o numero que recebe. A
 * consulta do placar usa DENSE_RANK, entao empates compartilham a mesma posicao
 * e duas linhas seguidas podem trazer "1" -- recalcular aqui (indice da lista,
 * contador local) quebraria o empate e mentiria sobre o resultado.
 *
 * O destaque dos tres primeiros reutiliza a mesma medalha vetorial do podio.
 */
import RankingMedalha from '@/Components/Atoms/Ranking/RankingMedalha.vue';

defineProps({
  /** Posicao ja apurada pelo backend. Nulo = participante sem classificacao no recorte. */
  posicao: {
    type: Number,
    default: null,
  },

  /** Falso mantem os tres primeiros com a mesma aparencia neutra dos demais. */
  destacarTopo: {
    type: Boolean,
    default: true,
  },
});

</script>
