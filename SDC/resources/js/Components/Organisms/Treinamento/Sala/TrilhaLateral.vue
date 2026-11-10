<script setup>
import { computed } from 'vue';

const props = defineProps({
  estado: { type: Object, required: true },
  itemAtualId: { type: Number, default: null },
});

const emit = defineEmits(['selecionar']);

const anel = computed(() => ({
  background: `conic-gradient(#f28c28 0 ${props.estado.percentual}%, #e2e8f0 0)`,
}));

const rotuloItens = computed(() => `${props.estado.concluidos} de ${props.estado.total} itens`);

function concluidosNaPasta(pasta) {
  return pasta.itens.filter((i) => i.status === 'CONCLUIDO').length;
}

function estadoDoItem(item) {
  if (!item.liberado) return 'bloqueado';
  if (item.status === 'CONCLUIDO') return 'concluido';
  return item.id === props.itemAtualId ? 'atual' : 'pendente';
}

function selecionar(item) {
  if (item.liberado) emit('selecionar', item.id);
}
</script>

<template>
  <nav class="border-b border-slate-200 bg-slate-50 text-sm dark:border-slate-700 dark:bg-slate-900/40 lg:border-b-0 lg:border-r" aria-label="Trilha do curso">
    <div class="flex items-center gap-3 border-b border-slate-200 px-4 py-3 dark:border-slate-700">
      <div class="flex h-11 w-11 flex-none items-center justify-center rounded-full" :style="anel">
        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-white text-[11px] font-bold dark:bg-slate-800">
          {{ Math.round(estado.percentual) }}%
        </span>
      </div>
      <div>
        <p class="font-semibold text-slate-800 dark:text-slate-100">Seu progresso</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ rotuloItens }}</p>
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
            <span class="min-w-0 flex-1 truncate">{{ item.titulo }}</span>
            <span v-if="item.tipo === 'PROJETO'" class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700">
              {{ item.entrega_label ?? 'PROJETO' }}
            </span>
          </button>
        </li>
      </ul>
    </div>
  </nav>
</template>
