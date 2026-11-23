<template>
  <TdapPainel rotulo="Satisfação" titulo="Satisfação com os cursos" subtitulo="Avaliação dos concluintes, nota de 1 (péssimo) a 5 (excelente)">
    <ListEmptyState
      v-if="!satisfacao.n"
      :icon="CheckBadgeIcon"
      title="Sem avaliações no recorte"
      helper="Nenhum concluinte avaliou os cursos para os filtros escolhidos."
    />

    <div v-else class="grid grid-cols-1 divide-y divide-slate-200 dark:divide-slate-700/40 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
      <div class="min-w-0 px-5 py-4">
        <div class="flex flex-wrap items-end gap-x-6 gap-y-2">
          <p>
            <span class="text-4xl font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ media(satisfacao.media) }}</span>
            <span class="ml-1 text-sm text-slate-500 dark:text-slate-400">de 5</span>
          </p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            {{ rotuloContagem(satisfacao.n, 'avaliação', 'avaliações') }}
          </p>
        </div>

        <ul class="mt-4 space-y-2.5">
          <li v-for="nota in distribuicao" :key="nota.nota" class="grid grid-cols-[5.5rem_minmax(0,1fr)_auto] items-center gap-3">
            <span class="truncate text-xs font-medium text-slate-700 dark:text-slate-200">{{ nota.rotulo }}</span>
            <span class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <span class="block h-full rounded-full" :style="{ width: `${nota.pct}%`, backgroundColor: corDaNota(nota.nota) }"></span>
            </span>
            <span class="w-24 text-right text-xs tabular-nums text-slate-600 dark:text-slate-300">
              <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatarNumero(nota.total) }}</span>
              ({{ formatarDecimal(nota.pct) }}%)
            </span>
          </li>
        </ul>
      </div>

      <div class="min-w-0 py-4">
        <p class="px-5 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ tituloRanking }}</p>
        <p v-if="ranking.length === 0" class="px-5 py-4 text-sm text-slate-500 dark:text-slate-400">Sem avaliações para comparar.</p>
        <ol v-else class="mt-2 divide-y divide-slate-200 dark:divide-slate-700/40">
          <li v-for="(linha, indice) in visiveis" :key="linha.chave" class="px-5 py-2.5">
            <div class="flex items-center gap-3">
              <span class="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-400 dark:text-slate-500">{{ indice + 1 }}</span>
              <div class="min-w-0 flex-1">
                <div class="flex items-baseline justify-between gap-3">
                  <p class="flex min-w-0 items-center gap-1.5 text-sm font-medium text-slate-800 dark:text-slate-100">
                    <span class="truncate" :title="linha.nome">{{ linha.nome }}</span>
                    <span v-if="linha.versao" class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ linha.versao }}</span>
                  </p>
                  <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ media(linha.media) }}</p>
                </div>
                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div class="h-full rounded-full bg-amber-500" :style="{ width: `${Math.max(2, (Number(linha.media ?? 0) / 5) * 100)}%` }"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ rotuloContagem(linha.n, 'avaliação', 'avaliações') }}</p>
              </div>
            </div>
          </li>
        </ol>
        <button
          v-if="temMais"
          type="button"
          class="mt-1 inline-flex min-h-[44px] w-full items-center justify-center text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
          :aria-expanded="aberta"
          @click="alternar"
        >
          {{ aberta ? 'Ver menos' : `Ver todos (${ranking.length})` }}
        </button>
      </div>
    </div>
  </TdapPainel>
</template>

<script setup>
/**
 * Satisfacao (props.satisfacao): media, numero de avaliacoes e distribuicao por
 * nota, com as cores e percentuais que o servidor ja manda. O ranking ao lado
 * compara cursos quando nao ha curso/turma filtrado, e turmas (com a versao do
 * curso EAD) quando ha.
 */
import { computed } from 'vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { useListaVerTodos } from '@/Composables/treinamento/useListaVerTodos';
import { corDaNota, formatarDecimal, formatarNumero, rotuloContagem } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  satisfacao: {
    type: Object,
    default: () => ({ n: 0, media: null, distribuicao: [], por_curso: [], por_turma: [] }),
  },
  filtros: { type: Object, required: true },
});

const media = (valor) => (valor === null || valor === undefined ? '—' : formatarDecimal(valor));

const distribuicao = computed(() => props.satisfacao.distribuicao ?? []);

const porTurma = computed(() => Boolean(props.filtros.curso_id || props.filtros.treinamento_id));

const tituloRanking = computed(() => (porTurma.value ? 'Média por turma' : 'Média por curso'));

const ranking = computed(() => (porTurma.value
  ? (props.satisfacao.por_turma ?? []).map((t) => ({ chave: `t:${t.treinamento_id}`, nome: t.titulo, versao: t.versao, n: t.n, media: t.media }))
  : (props.satisfacao.por_curso ?? []).map((c, i) => ({ chave: `c:${c.curso_id ?? `sem-${i}`}`, nome: c.nome, versao: null, n: c.n, media: c.media }))));

const { aberta, visiveis, temMais, alternar } = useListaVerTodos(() => ranking.value);
</script>
