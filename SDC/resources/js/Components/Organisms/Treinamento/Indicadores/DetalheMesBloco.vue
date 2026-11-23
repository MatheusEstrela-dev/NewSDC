<template>
  <section class="border-t border-slate-200 dark:border-slate-700/40" :aria-label="`Detalhe de ${rotuloMes(mes)}`" aria-live="polite">
    <header class="flex items-center justify-between gap-3 px-5 py-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Detalhe do mês</p>
        <h4 class="text-sm font-semibold capitalize text-slate-900 dark:text-slate-100">{{ rotuloMes(mes) }}</h4>
      </div>
      <button
        type="button"
        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
        title="Fechar o detalhe do mês"
        aria-label="Fechar o detalhe do mês"
        @click="$emit('fechar')"
      >
        <XMarkIcon class="h-4 w-4" />
      </button>
    </header>

    <div v-if="!detalhe" class="space-y-2 px-5 pb-5" aria-busy="true">
      <div class="h-10 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800"></div>
      <div class="h-16 animate-pulse rounded-lg bg-slate-100 dark:bg-slate-800"></div>
    </div>

    <template v-else>
      <div class="px-5 pb-4">
        <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4">
          <div v-for="total in totais" :key="total.rotulo" class="rounded-lg bg-slate-50 px-3 py-2 dark:bg-slate-800/60">
            <dt class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
              <span v-if="total.cor" class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: total.cor }"></span>
              {{ total.rotulo }}
            </dt>
            <dd class="text-xl font-bold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(total.valor) }}</dd>
          </div>
        </dl>
        <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ formatarNumero(detalhe.participantes) }} participantes no mês</p>
        <SituacaoBarras class="mt-1.5" :linha="detalhe" />
      </div>

      <ListEmptyState
        v-if="!detalhe.cursos?.length"
        :icon="BookOpenIcon"
        title="Nenhuma turma no mês"
        helper="Não há participações com referência neste mês."
      />
      <ul v-else class="divide-y divide-slate-200 border-t border-slate-200 dark:divide-slate-700/40 dark:border-slate-700/40">
        <li v-for="curso in detalhe.cursos" :key="curso.treinamento_id" class="px-5 py-3">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <p class="text-sm font-medium text-slate-800 dark:text-slate-100">
                <span class="line-clamp-2" :title="curso.titulo">{{ curso.titulo }}</span>
              </p>
              <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                <span v-if="curso.versao" class="rounded bg-slate-100 px-1.5 py-0.5 font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ curso.versao }}</span>
                <span v-if="curso.curso && curso.curso !== curso.titulo" class="min-w-0 truncate">{{ curso.curso }}</span>
                <span class="whitespace-nowrap">{{ formatarPeriodo(curso.data_inicio, curso.data_fim ?? curso.prazo) }}</span>
              </p>
            </div>
            <p class="shrink-0 text-right">
              <span class="block text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(curso.participantes) }}</span>
              <span class="block text-[11px] text-slate-500 dark:text-slate-400">participantes</span>
            </p>
          </div>
          <SituacaoBarras class="mt-2" :linha="curso" legenda fina />
        </li>
      </ul>
    </template>
  </section>
</template>

<script setup>
/**
 * Detalhe do mes clicado no grafico mensal (prop lazy detalheMes): totais por
 * situacao e as turmas com referencia no mes. `detalhe` null = ainda
 * carregando; quem pede o dado e quem limpa a selecao e a Index.
 */
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import SituacaoBarras from '@/Components/Organisms/Treinamento/Indicadores/SituacaoBarras.vue';
import { computed } from 'vue';
import { SITUACOES, formatarNumero, formatarPeriodo, rotuloMes } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  /** 'YYYY-MM' selecionado. */
  mes: { type: String, required: true },
  detalhe: { type: Object, default: null },
});

/** Os quatro numeros do mes: quantas capacitacoes e as pessoas por situacao. */
const totais = computed(() => (props.detalhe ? [
  { rotulo: 'Capacitações', valor: props.detalhe.cursos?.length ?? 0, cor: null },
  ...SITUACOES.map((s) => ({ rotulo: s.nome, valor: Number(props.detalhe[s.chave] ?? 0), cor: s.cor })),
] : []));

defineEmits(['fechar']);
</script>
