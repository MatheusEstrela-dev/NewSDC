<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" aria-labelledby="carteira-composicao-titulo" data-carteira-composicao>
    <h2 id="carteira-composicao-titulo" class="text-base font-bold text-slate-900 dark:text-white">De onde vem o saldo</h2>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Pontos confirmados do placar. Só entram no saldo resgatável depois de {{ numero(carteira.carencia_dias) }} dias, sem ajuste pendente e fora da demonstração.
    </p>

    <div v-if="total > 0" class="mt-4 flex h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700" role="img" :aria-label="descricaoDaBarra">
      <span v-for="parte in partesVisiveis" :key="parte.chave" :class="parte.cor" :style="{ width: `${(parte.valor / total) * 100}%` }" />
    </div>

    <ul class="mt-4 grid gap-3 sm:grid-cols-2">
      <li v-for="parte in partes" :key="parte.chave" class="flex items-start gap-3 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/60">
        <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full" :class="parte.cor" aria-hidden="true" />
        <div class="min-w-0">
          <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ parte.titulo }} · <span class="tabular-nums">{{ numero(parte.valor) }}</span>
          </p>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ parte.explicacao }}</p>
        </div>
      </li>
    </ul>

    <p v-if="carteira.reservado > 0 || carteira.debitado > 0" class="mt-4 text-xs text-slate-600 dark:text-slate-300">
      Já comprometido em resgates: {{ numero(carteira.reservado) }} reservados e {{ numero(carteira.debitado) }} debitados.
    </p>
  </section>
</template>

<script setup>
/**
 * Composicao da carteira de resgate: quanto do ponto confirmado ja e
 * resgatavel e por que o resto ainda nao e (carencia, ajuste, demonstracao).
 * O calculo e do backend (CalcularCarteira); aqui so a apresentacao.
 */
import { computed } from 'vue';

const props = defineProps({
  carteira: { type: Object, required: true },
});

const formatador = new Intl.NumberFormat('pt-BR');
const numero = (valor) => formatador.format(Number(valor ?? 0));
const data = (iso) => (iso ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short' }).format(new Date(iso)) : '');

const partes = computed(() => [
  { chave: 'maduro', titulo: 'Maduro', valor: Number(props.carteira.maduro), cor: 'bg-emerald-500', explicacao: 'Passou da carência e conta para o saldo resgatável.' },
  {
    chave: 'carencia', titulo: 'Em carência', valor: Number(props.carteira.em_carencia), cor: 'bg-amber-400',
    explicacao: props.carteira.proxima_liberacao ? `Os primeiros pontos liberam em ${data(props.carteira.proxima_liberacao)}.` : 'Nenhum ponto aguardando carência.',
  },
  { chave: 'ajuste', titulo: 'Em ajuste', valor: Number(props.carteira.em_ajuste), cor: 'bg-orange-500', explicacao: 'Há pedido de correção pendente; fica fora até a decisão.' },
  { chave: 'demo', titulo: 'Demonstração', valor: Number(props.carteira.demonstracao), cor: 'bg-slate-400', explicacao: 'Dados de homologação. Nunca são resgatáveis.' },
]);
const partesVisiveis = computed(() => partes.value.filter((parte) => parte.valor > 0));
const total = computed(() => partesVisiveis.value.reduce((soma, parte) => soma + parte.valor, 0));
const descricaoDaBarra = computed(() => partesVisiveis.value.map((parte) => `${parte.titulo}: ${numero(parte.valor)}`).join(', '));
</script>
