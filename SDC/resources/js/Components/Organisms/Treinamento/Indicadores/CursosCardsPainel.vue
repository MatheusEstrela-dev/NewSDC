<template>
  <TdapPainel rotulo="Cursos" titulo="Participação por curso" subtitulo="Clique num curso para filtrar o painel">
    <ListEmptyState
      v-if="cards.length === 0"
      :icon="BookOpenIcon"
      title="Nenhum curso com participante"
      helper="Não há participações para os filtros escolhidos."
    />

    <!--
      Ate xl: carrossel DENTRO do painel (overflow-x-auto + snap), a pagina nao
      rola de lado. Em xl vira lista vertical com rolagem propria, da altura do
      grafico mensal ao lado.
    -->
    <ul
      v-else
      class="flex min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto p-4 xl:max-h-[30rem] xl:flex-col xl:overflow-y-auto xl:overflow-x-hidden"
    >
      <li v-for="card in cards" :key="card.chave" class="w-[78%] shrink-0 snap-start sm:w-64 xl:w-full">
        <button
          type="button"
          class="block w-full rounded-xl border bg-white p-4 text-left shadow-sm transition-colors hover:border-orange-400 dark:bg-slate-900/40 dark:hover:border-orange-500/60"
          :class="card.ativo ? 'border-orange-400 dark:border-orange-500/60' : 'border-slate-200 dark:border-slate-700/40'"
          :title="card.ativo ? 'Remover o filtro deste curso' : `Filtrar por ${card.nome}`"
          :aria-pressed="card.ativo"
          @click="selecionar(card)"
        >
          <p class="line-clamp-2 text-sm font-medium text-slate-800 dark:text-slate-100" :title="card.nome">{{ card.nome }}</p>
          <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
            {{ rotuloContagem(card.turmas, 'turma', 'turmas') }}
          </p>

          <div class="mt-2 flex items-end justify-between gap-3">
            <div class="min-w-0">
              <p class="text-2xl font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ formatarNumero(card.participantes) }}</p>
              <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">participantes</p>
            </div>
            <div class="shrink-0 text-right">
              <p class="text-lg font-bold leading-none tabular-nums" :style="{ color: COR_DESISTENTE }">{{ formatarDecimal(card.taxa_desistencia_pct) }}%</p>
              <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">desistência</p>
            </div>
          </div>

          <div class="mt-3 border-t border-slate-200 pt-2.5 dark:border-slate-700/40">
            <div class="grid grid-cols-2 gap-3">
              <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Satisfação</p>
                <p class="mt-0.5 text-lg font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ mediaOuTraco(card.satisfacao.n, card.satisfacao.media) }}</p>
              </div>
              <div class="min-w-0">
                <p class="text-[11px] font-medium uppercase tracking-wider text-slate-500 dark:text-slate-400">Relevância</p>
                <p class="mt-0.5 text-lg font-bold leading-none tabular-nums text-slate-900 dark:text-slate-100">{{ mediaOuTraco(card.satisfacao.n_relevancia, card.satisfacao.media_relevancia) }}</p>
              </div>
            </div>
            <p class="mt-1.5 text-[11px] tabular-nums text-slate-500 dark:text-slate-400">
              {{ rotuloContagem(card.satisfacao.n, 'avaliação', 'avaliações') }} · {{ rotuloContagem(card.satisfacao.comentarios, 'comentário', 'comentários') }}
            </p>
            <div
              v-if="card.satisfacao.n > 0"
              class="mt-1.5 flex h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
              role="img"
              :aria-label="descreverNotas(card.satisfacao)"
              :title="descreverNotas(card.satisfacao)"
            >
              <span
                v-for="nota in card.satisfacao.notas.filter((n) => n.total > 0)"
                :key="nota.nota"
                class="h-full"
                :style="{ width: `${(nota.total / card.satisfacao.n) * 100}%`, backgroundColor: corDaNota(nota.nota) }"
              ></span>
            </div>
          </div>

          <SituacaoBarras class="mt-3" :linha="card" legenda />
        </button>
      </li>
    </ul>
  </TdapPainel>
</template>

<script setup>
/**
 * Um card por curso do catalogo (props.porCurso, top 15 do servidor, os de mais
 * matriculados primeiro): turmas, participantes, taxa de desistencia, medias de
 * satisfacao e de relevancia (sempre visiveis, traco sem resposta), quantidade de
 * avaliacoes e comentarios, a distribuicao das notas e as situacoes. Turma sem curso
 * vem como card proprio (curso_id null) e filtra pela turma. Clicar no card ja
 * filtrado volta para todos os cursos.
 */
import { computed } from 'vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import TdapPainel from '@/Components/Molecules/Tdap/TdapPainel.vue';
import SituacaoBarras from '@/Components/Organisms/Treinamento/Indicadores/SituacaoBarras.vue';
import { COR_DESISTENTE, corDaNota, formatarDecimal, formatarNumero, rotuloContagem } from '@/Composables/treinamento/useTemaGrafico';

const props = defineProps({
  porCurso: { type: Array, default: () => [] },
  filtros: { type: Object, required: true },
});

const emit = defineEmits(['selecionar']);

/** Media com 1 casa, ou traco quando ninguem respondeu. */
const mediaOuTraco = (n, media) => (Number(n) > 0 && media !== null && media !== undefined ? formatarDecimal(media) : '—');

const mesmoId = (a, b) => a !== null && a !== undefined && a !== '' && String(a) === String(b);

const cards = computed(() => props.porCurso.map((c) => ({
  ...c,
  ativo: c.curso_id !== null && c.curso_id !== undefined
    ? mesmoId(props.filtros.curso_id, c.curso_id)
    : mesmoId(props.filtros.treinamento_id, c.treinamento_id),
})));

/** Texto da barrinha de notas (tooltip e leitor de tela): "Excelente 12, Bom 3". */
const descreverNotas = (satisfacao) => satisfacao.notas
  .filter((n) => n.total > 0)
  .reverse()
  .map((n) => `${n.rotulo} ${formatarNumero(n.total)}`)
  .join(', ');

function selecionar(card) {
  if (card.ativo) {
    emit('selecionar', { curso_id: '', treinamento_id: '' });
  } else if (card.curso_id !== null && card.curso_id !== undefined) {
    emit('selecionar', { curso_id: String(card.curso_id), treinamento_id: '' });
  } else {
    emit('selecionar', { curso_id: '', treinamento_id: String(card.treinamento_id) });
  }
}
</script>
