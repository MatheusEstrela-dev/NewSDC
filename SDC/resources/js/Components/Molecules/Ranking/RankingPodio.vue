<template>
  <section aria-label="Pódio do placar" class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
    <header class="mb-3">
      <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Pódio</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">
        Três primeiros colocados por {{ rotuloDoEscopo }} no recorte atual. Empates compartilham a posição.
      </p>
    </header>

    <p v-if="!colocados.length" class="text-sm text-slate-500 dark:text-slate-400">
      Ainda não há colocados neste recorte.
    </p>

    <ol v-else class="grid list-none grid-cols-1 gap-3 p-0 sm:grid-cols-3">
      <li
        v-for="colocado in colocados"
        :key="`${colocado.posicao}-${colocado.entidade_id}`"
        :class="['min-w-0 rounded-lg border p-3', MOLDURAS[colocado.posicao] ?? MOLDURA_NEUTRA]"
      >
        <RankingPosicaoCell :posicao="colocado.posicao" />

        <p class="mt-2 truncate text-sm font-semibold text-slate-900 dark:text-slate-100" :title="identificacao(colocado)">
          {{ identificacao(colocado) }}
        </p>

        <p class="mt-1 text-xl font-bold tabular-nums text-slate-900 dark:text-slate-100">
          {{ pontos(colocado.pontos) }}
          <span class="text-xs font-normal text-slate-500 dark:text-slate-400">pontos</span>
        </p>

        <div class="mt-2">
          <RankingFaixaBadge :faixa="colocado.faixa" size="sm" />
        </div>
      </li>
    </ol>
  </section>
</template>

<script setup>
/**
 * Podio dos tres primeiros do placar.
 *
 * Tres situacoes que o layout precisa aguentar sem quebrar:
 *
 * 1. Menos de 3 participantes -- a grade tem 3 colunas, mas so renderiza o que
 *    existe; com zero colocados entra o texto de vazio.
 * 2. Empates -- a consulta usa DENSE_RANK, entao pode haver dois "1o lugar" e
 *    nenhum "2o". Nao ha slot fixo por colocacao: cada colocado e um item da
 *    grade, que quebra linha sozinha quando passam de tres. A posicao exibida e
 *    sempre a que veio do backend, nunca o indice da lista.
 * 3. Tela estreita -- uma coluna ate sm, `min-w-0` + `truncate` no rotulo. Nada
 *    aqui tem largura minima maior que a tela, entao a pagina nao rola de lado.
 *
 * Sem avatar/foto: no piloto o participante e identificado por codigo.
 */
import { computed } from 'vue';
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import RankingPosicaoCell from '@/Components/Molecules/Ranking/RankingPosicaoCell.vue';

// Moldura do card por colocacao, no mesmo tom das cores do Badge (ouro, prata,
// bronze). Classes literais: o Tailwind so gera o que encontra escrito.
const MOLDURAS = {
  1: 'border-amber-300 bg-amber-50 dark:border-amber-500/30 dark:bg-amber-500/10',
  2: 'border-slate-400 bg-slate-100 dark:border-slate-500/40 dark:bg-slate-600/20',
  3: 'border-orange-300 bg-orange-50 dark:border-orange-500/30 dark:bg-orange-500/10',
};

const MOLDURA_NEUTRA = 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-900/40';

const ROTULOS_DE_ESCOPO = {
  usuario: 'participante',
  orgao: 'órgão',
  municipio: 'município',
};

const props = defineProps({
  /** Linhas do placar: { entidade_id, pontos, posicao, faixa, rotulo? }. */
  linhas: {
    type: Array,
    default: () => [],
  },

  /** usuario | orgao | municipio. Usado so no texto de apoio. */
  escopo: {
    type: String,
    default: 'usuario',
  },
});

const rotuloDoEscopo = computed(() => ROTULOS_DE_ESCOPO[props.escopo] ?? 'participante');

const colocados = computed(() => props.linhas
  .map((linha) => ({ ...linha, posicao: Number(linha?.posicao) }))
  .filter((linha) => Number.isFinite(linha.posicao) && linha.posicao >= 1 && linha.posicao <= 3)
  .sort((a, b) => a.posicao - b.posicao || Number(b.pontos ?? 0) - Number(a.pontos ?? 0)));

const pontos = (valor) => new Intl.NumberFormat('pt-BR').format(Number(valor ?? 0));

const identificacao = (colocado) => colocado.rotulo || `#${colocado.entidade_id}`;
</script>
