<script setup>
import { computed } from 'vue';

const props = defineProps({
  estado: { type: Object, required: true },
  itemAtualId: { type: Number, default: null },
  /** Percentual do curso com a avaliacao contando como etapa (SalaDeAulaTemplate). */
  percentual: { type: Number, required: true },
  /** Etapa final (avaliacao do curso): BLOQUEADA, LIBERADA ou ENVIADA. */
  avaliacao: { type: String, required: true },
  /** Curso ja concluido (inclusive legado, sem avaliacao). */
  concluido: { type: Boolean, default: false },
  avaliacaoAberta: { type: Boolean, default: false },
});

const emit = defineEmits(['selecionar', 'abrir-avaliacao']);

const anel = computed(() => ({
  background: `conic-gradient(#f28c28 0 ${props.percentual}%, #e2e8f0 0)`,
}));

const rotuloItens = computed(() => `${props.estado.concluidos} de ${props.estado.total} itens`);

const avaliacaoFeita = computed(() => props.avaliacao === 'ENVIADA');

// Mesmo vocabulario de estado dos itens: bloqueado, concluido, atual ou pendente.
const estadoDaAvaliacao = computed(() => {
  if (avaliacaoFeita.value) return 'concluido';
  if (props.avaliacao === 'BLOQUEADA') return 'bloqueado';
  return props.avaliacaoAberta ? 'atual' : 'pendente';
});

function concluidosNaPasta(pasta) {
  return pasta.itens.filter((i) => i.status === 'CONCLUIDO').length;
}

function estadoDoItem(item) {
  if (!item.liberado) return 'bloqueado';
  if (item.status === 'CONCLUIDO') return 'concluido';
  return item.id === props.itemAtualId ? 'atual' : 'pendente';
}

// Item bloqueado tambem abre: a area de conteudo mostra o motivo (ex.: prova depende dos demais itens da pasta).
function selecionar(item) {
  emit('selecionar', item.id);
}

function descricaoDoItem(item) {
  return item.liberado ? undefined : 'Bloqueado: conclua antes os itens obrigatórios desta pasta';
}
</script>

<template>
  <nav class="border-b border-slate-200 bg-slate-50 text-sm dark:border-slate-700 dark:bg-slate-900/40 lg:border-b-0 lg:border-r" aria-label="Trilha do curso">
    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
      <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full" :style="anel">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[11px] font-bold dark:bg-slate-800">
          {{ Math.round(percentual) }}%
        </span>
      </div>
      <div>
        <p class="font-semibold text-slate-800 dark:text-slate-100">Seu progresso</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ rotuloItens }}</p>
        <p v-if="avaliacaoFeita || concluido" class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">Curso concluído</p>
        <p v-else-if="avaliacao === 'LIBERADA'" class="text-xs font-semibold text-amber-600 dark:text-amber-400">Falta avaliar o curso</p>
      </div>
    </div>

    <div v-for="(pasta, indice) in estado.pastas" :key="pasta.id" :class="{ 'opacity-60': !pasta.liberada }">
      <div class="flex items-center gap-2 border-t border-slate-200 px-4 py-2.5 font-semibold text-slate-700 first:border-t-0 dark:border-slate-700 dark:text-slate-200">
        <span
          class="inline-flex h-5 w-5 flex-none items-center justify-center rounded-full text-[11px] font-bold"
          :class="pasta.concluida
            ? 'bg-emerald-600 text-white'
            : pasta.liberada ? 'border-2 border-amber-500 text-amber-600' : 'bg-slate-200 text-slate-400'"
        >
          <template v-if="pasta.concluida">&#10003;</template>
          <svg v-else-if="!pasta.liberada" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-label="Pasta bloqueada">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
          </svg>
          <template v-else>{{ indice + 1 }}</template>
        </span>
        <span class="min-w-0 flex-1 truncate">{{ pasta.titulo }}</span>
        <small class="font-medium text-slate-500 dark:text-slate-400">{{ concluidosNaPasta(pasta) }}/{{ pasta.itens.length }}</small>
      </div>

      <ul v-if="pasta.liberada">
        <li v-for="item in pasta.itens" :key="item.id">
          <button
            type="button"
            class="flex w-full items-center gap-2.5 py-2 pl-8 pr-4 text-left hover:bg-slate-100 dark:hover:bg-slate-800"
            :class="item.id === itemAtualId ? 'border-l-[3px] border-amber-500 bg-amber-50 pl-[29px] font-semibold dark:bg-amber-500/10' : ''"
            :aria-current="item.id === itemAtualId ? 'true' : undefined"
            :aria-disabled="item.liberado ? undefined : 'true'"
            :title="descricaoDoItem(item)"
            @click="selecionar(item)"
          >
            <span
              class="inline-flex h-5 w-5 flex-none items-center justify-center rounded-full text-[11px] font-bold"
              :class="{
                'bg-emerald-600 text-white': estadoDoItem(item) === 'concluido',
                'border-2 border-amber-500 text-amber-600': estadoDoItem(item) === 'atual',
                'border-2 border-slate-300': estadoDoItem(item) === 'pendente',
              }"
            >
              <template v-if="estadoDoItem(item) === 'concluido'">&#10003;</template>
              <template v-else-if="estadoDoItem(item) === 'atual'">&#9654;</template>
            </span>
            <span class="min-w-0 flex-1 truncate" :class="{ 'text-slate-400 dark:text-slate-500': !item.liberado }">{{ item.titulo }}</span>
            <span v-if="!item.liberado && item.tipo !== 'PROVA'" class="text-[10px] font-semibold text-slate-500 dark:text-slate-400">Bloqueado</span>
            <span v-else-if="item.tipo === 'PROJETO'" class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
              {{ item.entrega_label ?? 'PROJETO' }}
            </span>
            <span
              v-else-if="item.tipo === 'PROVA'"
              class="inline-flex flex-none items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold"
              :class="item.prova?.travada ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700'"
            >
              <svg v-if="estadoDoItem(item) === 'bloqueado'" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-label="Prova bloqueada">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
              {{ item.prova?.travada ? 'TRAVADA' : 'PROVA' }}
            </span>
          </button>
        </li>
      </ul>
    </div>

    <!-- Etapa final gerada pelo sistema (nao e pasta do professor): a avaliacao do curso. -->
    <div :class="{ 'opacity-60': avaliacao === 'BLOQUEADA' }">
      <div class="flex items-center gap-2 border-t border-slate-200 px-4 py-2.5 font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
        <span
          class="inline-flex h-5 w-5 flex-none items-center justify-center rounded-full text-[11px] font-bold"
          :class="avaliacaoFeita
            ? 'bg-emerald-600 text-white'
            : avaliacao === 'LIBERADA' ? 'border-2 border-amber-500 text-amber-600' : 'bg-slate-200 text-slate-400'"
        >
          <template v-if="avaliacaoFeita">&#10003;</template>
          <svg v-else-if="avaliacao === 'BLOQUEADA'" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-label="Etapa bloqueada">
            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
          </svg>
          <template v-else>&#9733;</template>
        </span>
        <span class="min-w-0 flex-1 truncate">Etapa final</span>
        <small class="font-medium text-slate-500 dark:text-slate-400">{{ avaliacaoFeita ? 1 : 0 }}/1</small>
      </div>

      <ul>
        <li>
          <button
            type="button"
            class="flex w-full items-center gap-2.5 py-2 pl-8 pr-4 text-left hover:bg-slate-100 dark:hover:bg-slate-800"
            :class="avaliacaoAberta ? 'border-l-[3px] border-amber-500 bg-amber-50 pl-[29px] font-semibold dark:bg-amber-500/10' : ''"
            :aria-current="avaliacaoAberta ? 'true' : undefined"
            :title="avaliacao === 'BLOQUEADA' ? 'Liberada ao concluir todos os módulos' : undefined"
            @click="emit('abrir-avaliacao')"
          >
            <span
              class="inline-flex h-5 w-5 flex-none items-center justify-center rounded-full text-[11px] font-bold"
              :class="{
                'bg-emerald-600 text-white': estadoDaAvaliacao === 'concluido',
                'border-2 border-amber-500 text-amber-600': estadoDaAvaliacao === 'atual',
                'border-2 border-slate-300': estadoDaAvaliacao === 'pendente' || estadoDaAvaliacao === 'bloqueado',
              }"
            >
              <template v-if="estadoDaAvaliacao === 'concluido'">&#10003;</template>
              <template v-else-if="estadoDaAvaliacao === 'atual'">&#9654;</template>
            </span>
            <span class="min-w-0 flex-1 truncate">Avaliação do curso</span>
            <span
              class="inline-flex flex-none items-center gap-1 rounded px-1.5 py-0.5 text-[10px] font-bold"
              :class="avaliacaoFeita ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
            >
              <svg v-if="estadoDaAvaliacao === 'bloqueado'" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-label="Avaliação bloqueada">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
              </svg>
              {{ avaliacaoFeita ? 'ENVIADA' : 'OBRIGATÓRIA' }}
            </span>
          </button>
        </li>
      </ul>
    </div>
  </nav>
</template>
