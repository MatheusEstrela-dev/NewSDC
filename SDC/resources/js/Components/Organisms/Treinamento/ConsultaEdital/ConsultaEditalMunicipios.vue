<template>
  <ListEmptyState
    v-if="linhas.length === 0"
    :icon="MapIcon"
    title="Nenhum município encontrado"
    helper="Ninguém concluiu os cursos escolhidos para o recorte informado."
  />
  <ol v-else class="divide-y divide-slate-200 dark:divide-slate-700/40">
    <li v-for="(l, indice) in visiveis" :key="l.municipio_id ?? 'sem-municipio'" class="px-5 py-2.5">
      <div class="flex items-center gap-3">
        <span class="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-400 dark:text-slate-500">{{ indice + 1 }}</span>
        <div class="min-w-0 flex-1">
          <div class="flex items-baseline justify-between gap-3">
            <p class="min-w-0 truncate text-sm font-medium text-slate-800 dark:text-slate-100" :title="l.municipio">{{ l.municipio }}</p>
            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(l.pessoas) }}</p>
          </div>
          <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ l.redec ?? 'Sem REDEC' }}</p>
        </div>
      </div>
    </li>
    <li v-if="temMais" class="px-5">
      <button
        type="button"
        class="inline-flex min-h-[44px] w-full items-center justify-center text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
        :aria-expanded="aberta"
        @click="alternar"
      >
        {{ aberta ? 'Ver menos' : `Ver todos (${linhas.length})` }}
      </button>
    </li>
  </ol>
</template>

<script setup>
/** Aba "Municípios": pessoas qualificadas por município da pessoa, do maior para o menor. */
import MapIcon from '@/Components/Icons/MapIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useListaVerTodos } from '@/Composables/treinamento/useListaVerTodos';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  /** [{ municipio_id, municipio, redec, pessoas }] */
  linhas: { type: Array, default: () => [] },
});

const { aberta, visiveis, temMais, alternar } = useListaVerTodos(() => props.linhas, 20);
</script>
