<template>
  <TdapPainel rotulo="Satisfação" titulo="Satisfação com os cursos" subtitulo="Pesquisa dos concluintes: satisfação geral, relevância do conteúdo e sugestões">
    <ListEmptyState
      v-if="!satisfacao.n"
      :icon="CheckBadgeIcon"
      title="Sem avaliações no recorte"
      helper="Nenhum concluinte avaliou os cursos para os filtros escolhidos."
    />

    <div v-else class="grid grid-cols-1 divide-y divide-slate-200 dark:divide-slate-700/40 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
      <div class="min-w-0 space-y-6 px-5 py-4">
        <section v-for="eixo in eixos" :key="eixo.chave" :aria-label="eixo.titulo">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ eixo.titulo }}</p>
          <div class="mt-2 flex flex-wrap items-end gap-x-6 gap-y-2">
            <p>
              <span class="text-4xl font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ media(eixo.media) }}</span>
              <span class="ml-1 text-sm text-slate-500 dark:text-slate-400">de 5</span>
            </p>
            <p class="text-sm text-slate-600 dark:text-slate-300">
              {{ rotuloContagem(eixo.n, eixo.singular, eixo.plural) }}
            </p>
          </div>

          <p v-if="!eixo.n" class="mt-3 text-sm text-slate-500 dark:text-slate-400">Ainda sem respostas neste recorte.</p>
          <ul v-else class="mt-3 space-y-2.5">
            <li v-for="nota in eixo.distribuicao" :key="nota.nota" class="grid grid-cols-[7.5rem_minmax(0,1fr)_auto] items-center gap-3">
              <span class="truncate text-xs font-medium text-slate-700 dark:text-slate-200" :title="nota.rotulo">{{ nota.rotulo }}</span>
              <span class="h-2.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                <span class="block h-full rounded-full" :style="{ width: `${nota.pct}%`, backgroundColor: corDaNota(nota.nota) }"></span>
              </span>
              <span class="w-24 text-right text-xs tabular-nums text-slate-600 dark:text-slate-300">
                <span class="font-semibold text-slate-900 dark:text-slate-100">{{ formatarNumero(nota.total) }}</span>
                ({{ formatarDecimal(nota.pct) }}%)
              </span>
            </li>
          </ul>
        </section>
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
                  <p class="shrink-0 text-right text-sm font-semibold tabular-nums text-slate-900 dark:text-slate-100">
                    <span title="Média de satisfação geral">{{ media(linha.media) }}</span>
                    <span class="ml-1 text-xs font-normal text-slate-500 dark:text-slate-400" title="Média de relevância do conteúdo">· relevância {{ media(linha.media_relevancia) }}</span>
                  </p>
                </div>
                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                  <div class="h-full rounded-full bg-amber-500" :style="{ width: `${Math.max(2, (Number(linha.media ?? 0) / 5) * 100)}%` }"></div>
                </div>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                  {{ rotuloContagem(linha.n, 'avaliação', 'avaliações') }} · {{ rotuloContagem(linha.comentarios, 'sugestão', 'sugestões') }}
                </p>
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

      <section
        class="min-w-0 border-t border-slate-200 px-5 py-4 dark:border-slate-700/40 lg:col-span-2"
        aria-label="Sugestões e comentários"
      >
        <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sugestões e comentários</p>
          <p class="text-sm text-slate-600 dark:text-slate-300">
            <span class="font-semibold tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(comentariosTotal) }}</span>
            {{ comentariosTotal === 1 ? 'comentário no recorte' : 'comentários no recorte' }}
          </p>
        </div>

        <p v-if="lista.length === 0 && !carregandoTodos" class="mt-3 text-sm text-slate-500 dark:text-slate-400">Nenhuma sugestão escrita neste recorte.</p>
        <template v-else>
          <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
            {{ legendaLista }}
          </p>
          <ul class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2" :class="{ 'opacity-60': carregandoTodos }" :aria-busy="carregandoTodos">
            <li
              v-for="sugestao in lista"
              :key="sugestao.id"
              class="min-w-0 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700/40 dark:bg-slate-900/40"
            >
              <p
                :ref="(el) => registrarTexto(sugestao.id, el)"
                class="whitespace-pre-line break-words text-sm text-slate-700 dark:text-slate-200"
                :class="{ 'line-clamp-3': !expandidas.has(sugestao.id) }"
              >{{ sugestao.comentario }}</p>
              <button
                v-if="transbordam.has(sugestao.id) || expandidas.has(sugestao.id)"
                type="button"
                class="mt-1 inline-flex min-h-[32px] items-center text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400"
                :aria-expanded="expandidas.has(sugestao.id)"
                @click="alternarSugestao(sugestao.id)"
              >
                {{ expandidas.has(sugestao.id) ? 'Ver menos' : 'Ver mais' }}
              </button>
              <p class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-[11px] text-slate-500 dark:text-slate-400">
                <span class="truncate font-medium" :title="sugestao.titulo">{{ sugestao.curso ?? sugestao.titulo }}</span>
                <span v-if="sugestao.curso && sugestao.titulo !== sugestao.curso" class="truncate" :title="sugestao.titulo">· {{ sugestao.titulo }}</span>
                <span v-if="sugestao.versao" class="shrink-0 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ sugestao.versao }}</span>
                <span v-if="sugestao.data" class="shrink-0 tabular-nums">· {{ formatarData(sugestao.data) }}</span>
              </p>
            </li>
          </ul>

          <div v-if="comentariosTotal > sugestoes.length" class="mt-3 flex flex-wrap items-center gap-3">
            <button
              type="button"
              class="inline-flex min-h-[36px] items-center rounded-lg border border-slate-200 px-3 text-xs font-semibold text-blue-600 hover:bg-slate-50 dark:border-slate-700/40 dark:text-blue-400 dark:hover:bg-slate-800"
              :aria-expanded="verTodos"
              @click="alternarVerTodos"
            >
              {{ verTodos ? 'Mostrar só as mais recentes' : 'Ver todos os comentários' }}
            </button>
            <template v-if="verTodos && comentarios">
              <button type="button" class="min-h-[36px] text-xs font-semibold text-slate-600 disabled:opacity-40 dark:text-slate-300" :disabled="carregandoTodos || comentarios.pagina <= 1" @click="irParaPaginaComentarios(comentarios.pagina - 1)">Anterior</button>
              <span class="text-xs tabular-nums text-slate-500 dark:text-slate-400">Página {{ comentarios.pagina }} de {{ comentarios.ultima_pagina }}</span>
              <button type="button" class="min-h-[36px] text-xs font-semibold text-slate-600 disabled:opacity-40 dark:text-slate-300" :disabled="carregandoTodos || comentarios.pagina >= comentarios.ultima_pagina" @click="irParaPaginaComentarios(comentarios.pagina + 1)">Próxima</button>
            </template>
          </div>
        </template>
      </section>
    </div>
  </TdapPainel>
</template>

<script setup>
/**
 * Satisfacao (props.satisfacao): combo de tres perguntas da pesquisa do curso.
 * (1) satisfacao geral e (2) relevancia do conteudo: media, n e distribuicao
 * por nota, com as cores e percentuais que o servidor ja manda; (3) sugestoes:
 * total de comentarios e as mais recentes (sem autor, LGPD). O ranking ao lado
 * compara cursos quando nao ha curso/turma filtrado, e turmas (com a versao do
 * curso EAD) quando ha.
 */
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import { useListaVerTodos } from '@/Composables/treinamento/useListaVerTodos';
import { corDaNota, formatarData, formatarDecimal, formatarNumero, rotuloContagem } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  satisfacao: {
    type: Object,
    default: () => ({
      n: 0, media: null, distribuicao: [], n_relevancia: 0, media_relevancia: null, distribuicao_relevancia: [], comentarios_total: 0, sugestoes: [], por_curso: [], por_turma: [],
    }),
  },
  filtros: { type: Object, required: true },
  /** Lazy: pagina de "Ver todos os comentarios" ({ data, pagina, ultima_pagina, total }). */
  comentarios: { type: Object, default: null },
});

const media = (valor) => (valor === null || valor === undefined ? '—' : formatarDecimal(valor));

// Duas perguntas de nota da pesquisa: satisfacao geral e relevancia do conteudo (RN17).
const eixos = computed(() => [
  {
    chave: 'geral',
    titulo: 'Satisfação geral',
    n: props.satisfacao.n,
    media: props.satisfacao.media,
    distribuicao: props.satisfacao.distribuicao ?? [],
    singular: 'avaliação',
    plural: 'avaliações',
  },
  {
    chave: 'relevancia',
    titulo: 'Relevância do conteúdo',
    n: props.satisfacao.n_relevancia ?? 0,
    media: props.satisfacao.media_relevancia ?? null,
    distribuicao: props.satisfacao.distribuicao_relevancia ?? [],
    singular: 'resposta',
    plural: 'respostas',
  },
]);

const porTurma = computed(() => Boolean(props.filtros.curso_id || props.filtros.treinamento_id));

const tituloRanking = computed(() => (porTurma.value ? 'Média por turma' : 'Média por curso'));

const ranking = computed(() => (porTurma.value
  ? (props.satisfacao.por_turma ?? []).map((t) => ({ chave: `t:${t.treinamento_id}`, nome: t.titulo, versao: t.versao, n: t.n, media: t.media, media_relevancia: t.media_relevancia, comentarios: t.comentarios ?? 0 }))
  : (props.satisfacao.por_curso ?? []).map((c, i) => ({ chave: `c:${c.curso_id ?? `sem-${i}`}`, nome: c.nome, versao: null, n: c.n, media: c.media, media_relevancia: c.media_relevancia, comentarios: c.comentarios ?? 0 }))));

const { aberta, visiveis, temMais, alternar } = useListaVerTodos(() => ranking.value);

// Terceira pergunta: sugestoes. Texto longo fica truncado (3 linhas) ate "Ver mais";
// o botao so aparece quando o texto de fato transborda. A chave e o id do comentario.
const comentariosTotal = computed(() => props.satisfacao.comentarios_total ?? 0);
const sugestoes = computed(() => props.satisfacao.sugestoes ?? []);
const expandidas = reactive(new Set());
const alternarSugestao = (id) => (expandidas.has(id) ? expandidas.delete(id) : expandidas.add(id));

const verTodos = ref(false);
const carregandoTodos = ref(false);
const lista = computed(() => (verTodos.value && props.comentarios ? props.comentarios.data : sugestoes.value));

const legendaLista = computed(() => {
  if (verTodos.value && props.comentarios) {
    return `Todos os ${formatarNumero(props.comentarios.total)} comentários, sem identificação do autor.`;
  }

  return sugestoes.value.length < comentariosTotal.value
    ? `As ${sugestoes.value.length} mais recentes, sem identificação do autor.`
    : 'Sem identificação do autor.';
});

function pedirComentarios(pagina) {
  carregandoTodos.value = true;
  router.reload({
    only: ['comentarios'],
    data: { comentarios_pagina: pagina },
    preserveState: true,
    preserveScroll: true,
    onFinish: () => { carregandoTodos.value = false; },
  });
}

const irParaPaginaComentarios = (pagina) => pedirComentarios(pagina);

function alternarVerTodos() {
  verTodos.value = !verTodos.value;
  expandidas.clear();
  if (verTodos.value) pedirComentarios(props.comentarios?.pagina ?? 1);
}

// Mudou o recorte (filtro): a lista paginada era do recorte antigo.
watch(() => props.satisfacao.comentarios_total, () => { verTodos.value = false; });

// Overflow real: mede o paragrafo truncado (scrollHeight > clientHeight).
const textos = new Map();
const transbordam = reactive(new Set());
const registrarTexto = (id, el) => (el ? textos.set(id, el) : textos.delete(id));

function medirTextos() {
  transbordam.clear();
  textos.forEach((el, id) => {
    if (el.scrollHeight > el.clientHeight + 1) transbordam.add(id);
  });
}

const medirDepois = () => nextTick(medirTextos);
watch(lista, medirDepois, { flush: 'post' });
onMounted(() => {
  medirDepois();
  window.addEventListener('resize', medirTextos);
});
onBeforeUnmount(() => window.removeEventListener('resize', medirTextos));
</script>
