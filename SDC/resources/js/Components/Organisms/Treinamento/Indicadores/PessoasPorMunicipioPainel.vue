<template>
  <TdapPainel rotulo="Território" titulo="Pessoas por município" subtitulo="Pessoas distintas por município onde elas atuam ou residem">
    <ListEmptyState
      v-if="linhas.length === 0"
      :icon="MapIcon"
      title="Nenhum município no recorte"
      helper="Não há capacitados para os filtros escolhidos."
    />
    <ol v-else class="divide-y divide-slate-200 dark:divide-slate-700/40">
      <li v-for="(linha, indice) in visiveis" :key="linha.municipio_id ?? 'sem-municipio'" class="px-5 py-2.5">
        <div class="flex items-center gap-3">
          <span class="w-6 shrink-0 text-right text-xs font-semibold tabular-nums text-slate-400 dark:text-slate-500">{{ indice + 1 }}</span>
          <div class="min-w-0 flex-1">
            <div class="flex items-baseline justify-between gap-3">
              <p class="min-w-0 truncate text-sm font-medium text-slate-800 dark:text-slate-100" :title="linha.nome">{{ linha.nome }}</p>
              <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(linha.pessoas) }}</p>
            </div>
            <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
              <div class="h-full rounded-full bg-emerald-500" :style="{ width: `${largura(linha.pessoas)}%` }"></div>
            </div>
            <p class="mt-1 truncate text-xs text-slate-500 dark:text-slate-400">{{ detalhe(linha) }}</p>
          </div>
        </div>
      </li>
    </ol>

    <template v-if="temMais" #rodape>
      <button
        type="button"
        class="inline-flex min-h-[44px] w-full items-center justify-center text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
        :aria-expanded="aberta"
        @click="alternar"
      >
        {{ aberta ? 'Ver menos' : `Ver todos (${linhas.length})` }}
      </button>
    </template>
  </TdapPainel>
</template>

<script setup>
/**
 * Pessoas distintas por municipio DA PESSOA, com a REDEC e a quantidade de
 * cursos distintos. A lista vem inteira do servidor; aqui so se mostra o topo
 * ou tudo.
 */
import { computed } from 'vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { useListaVerTodos } from '@/Composables/treinamento/useListaVerTodos';
import { formatarNumero } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  /** [{ municipio_id, nome, redec, pessoas, cursos }] em ordem de ranking. */
  linhas: { type: Array, default: () => [] },
});

const { aberta, visiveis, temMais, alternar } = useListaVerTodos(() => props.linhas);

const maximo = computed(() => Math.max(1, ...props.linhas.map((l) => l.pessoas)));
const largura = (valor) => Math.max(2, Math.round((valor / maximo.value) * 100));

const detalhe = (linha) => [
  linha.redec,
  `${formatarNumero(linha.cursos)} ${linha.cursos === 1 ? 'curso' : 'cursos'}`,
].filter(Boolean).join(' · ');
</script>
