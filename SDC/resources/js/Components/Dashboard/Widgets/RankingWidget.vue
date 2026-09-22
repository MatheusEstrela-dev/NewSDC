<script setup>
/**
 * Resumo do placar pessoal na Visao Geral.
 *
 * Mostra saldo, posicao e faixa do usuario da sessao. Os numeros vem do
 * RankingReadService::resumo() -- os mesmos da tela de Ranking -- e nunca de
 * uma contagem propria, senao as duas telas discordariam sobre a pontuacao.
 *
 * O modulo nasce com RANKING_HABILITADO=false, entao o caminho comum deste
 * widget e NAO ter dado nenhum: `resumo` chega nulo e o card cai no estado
 * vazio em vez de exibir zeros que pareceriam reais.
 *
 * Nao decide permissao: quem resolve se o widget aparece e o Dashboard, pela
 * flag por usuario `rankingDisponivel` (share por requisicao), que fica fora
 * do DTO cacheado das estatisticas.
 */
import RankingFaixaBadge from '@/Components/Atoms/Ranking/RankingFaixaBadge.vue';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  // Formato: { pontos, posicao, faixa, atualizado_em }. Nulo quando o modulo
  // esta desligado, quando o usuario nao tem vinculo ou quando a leitura falha.
  resumo: {
    type: Object,
    default: null,
  },
});

// route() lanca excecao para nome inexistente; sem a rota registrada o card
// continua informando, so nao oferece o link.
const temRota = computed(() => route().has('ranking.index'));

const pontos = computed(() => {
  const valor = Number(props.resumo?.pontos ?? 0);
  return Number.isFinite(valor) ? valor : 0;
});

const posicao = computed(() => {
  const valor = Number(props.resumo?.posicao);
  return Number.isFinite(valor) && valor > 0 ? valor : null;
});

const faixa = computed(() => props.resumo?.faixa ?? null);

// Sem resumo nao ha o que exibir: o modulo esta desligado ou a leitura falhou.
const semDados = computed(() => props.resumo === null || props.resumo === undefined);

// Com resumo e saldo zerado o usuario existe no placar, mas ainda nao pontuou.
const semPontos = computed(() => !semDados.value && pontos.value === 0);

const formatador = new Intl.NumberFormat('pt-BR');

const pontosFormatados = computed(() => formatador.format(pontos.value));
const posicaoFormatada = computed(() => (posicao.value === null ? '--' : formatador.format(posicao.value)));

const mensagemVazia = computed(() => {
  if (semDados.value) {
    return 'Placar ainda não disponível. Nenhuma pontuação foi apurada para o seu usuário.';
  }
  if (semPontos.value) {
    return 'Você ainda não tem pontos registrados no período.';
  }
  if (posicao.value === null) {
    return 'Sem vínculo institucional apurado: o saldo aparece, a posição no placar não.';
  }
  return '';
});
</script>

<template>
  <div class="flex h-full flex-col overflow-hidden rounded-xl border border-slate-100 bg-white shadow-lg dark:border-slate-800/50 dark:bg-slate-900/60">
    <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50/50 px-4 py-4 sm:px-5 dark:border-slate-800/50 dark:bg-slate-800/30">
      <div class="flex min-w-0 items-center gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4h8v5a4 4 0 11-8 0V4z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H5.5A1.5 1.5 0 004 6.5v.5a4 4 0 004 4m8-10h2.5A1.5 1.5 0 0120 6.5v.5a4 4 0 01-4 4" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 13v4m-3 3h6" />
          </svg>
        </div>
        <div class="min-w-0">
          <h3 class="truncate text-base font-bold text-slate-900 dark:text-slate-200">Meu Placar</h3>
          <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">Pontos, posição e faixa</p>
        </div>
      </div>

      <Link
        v-if="temRota"
        :href="route('ranking.index')"
        class="shrink-0 text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400"
      >
        Ver placar
      </Link>
    </div>

    <div class="flex flex-1 flex-col gap-4 p-4 sm:p-5">
      <div class="grid grid-cols-2 gap-3">
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/40">
          <p class="text-2xl font-bold leading-tight text-slate-900 dark:text-slate-100">{{ pontosFormatados }}</p>
          <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Pontos
          </p>
        </div>
        <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800/40">
          <p class="text-2xl font-bold leading-tight text-slate-900 dark:text-slate-100">{{ posicaoFormatada }}</p>
          <p class="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">
            Posição
          </p>
        </div>
      </div>

      <div v-if="faixa" class="flex items-center justify-between gap-3">
        <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Faixa atual</span>
        <RankingFaixaBadge :faixa="faixa" size="sm" />
      </div>

      <p v-if="mensagemVazia" class="mt-auto text-xs leading-relaxed text-slate-500 dark:text-slate-400">
        {{ mensagemVazia }}
      </p>
    </div>
  </div>
</template>
