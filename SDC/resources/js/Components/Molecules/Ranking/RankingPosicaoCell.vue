<template>
  <div class="flex items-center gap-2">
    <template v-if="posicao === null">
      <span aria-hidden="true" class="text-slate-400 dark:text-slate-500">—</span>
      <span class="sr-only">sem classificação</span>
    </template>

    <Badge v-else-if="corDoTopo" :cor="corDoTopo" size="sm" :aria-label="`${posicao}º lugar`">
      {{ posicao }}º
    </Badge>

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
 * O destaque dos tres primeiros reaproveita o Badge, mesma fonte de aparencia de
 * pill do resto do sistema. Cores literais ficam la dentro; aqui so trafega NOME
 * de cor, nunca classe interpolada.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

// Ouro, prata e bronze do podio. Mesmos nomes de cor do RankingFaixaBadge, de
// proposito: a leitura visual de "1o lugar" e de "faixa Ouro" precisa bater.
const CORES_DO_TOPO = {
  1: 'amber',
  2: 'slate-forte',
  3: 'orange',
};

const props = defineProps({
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

const corDoTopo = computed(() => (props.destacarTopo ? CORES_DO_TOPO[props.posicao] ?? null : null));
</script>
