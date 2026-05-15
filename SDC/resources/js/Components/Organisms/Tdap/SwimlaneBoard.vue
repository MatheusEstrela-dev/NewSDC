<template>
  <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4">
    <div
      v-for="board in boards"
      :key="board.value"
      class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden flex flex-col"
    >
      <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700/40" :class="headerCor(board.cor)">
        <h3 class="text-sm font-semibold">{{ board.label }}</h3>
        <p class="text-xs opacity-80 mt-0.5">{{ board.items.length }} processo(s)</p>
      </div>
      <div class="flex-1 p-3 space-y-2 max-h-[70vh] overflow-y-auto">
        <Link
          v-for="p in board.items"
          :key="p.id"
          :href="route('tdap.processos.show', p.id)"
          class="block rounded-lg border border-slate-200 dark:border-slate-700/40 bg-slate-50 dark:bg-slate-800/40 p-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-500/30 transition"
        >
          <p class="font-mono text-sm font-semibold text-slate-900 dark:text-slate-100">{{ p.numero }}</p>
          <p class="text-xs text-slate-500 mt-1">{{ p.estado_label }}</p>
          <p v-if="p.municipio_nome" class="text-xs text-slate-600 dark:text-slate-400 mt-1 truncate">
            {{ p.municipio_nome }}<span v-if="p.municipio_uf">/{{ p.municipio_uf }}</span>
          </p>
          <p v-if="p.aberto_em" class="text-[10px] text-slate-400 mt-1">
            Aberto em {{ fmtDate(p.aberto_em) }}
          </p>
        </Link>
        <p v-if="board.items.length === 0" class="text-center text-xs text-slate-400 py-6">
          Vazio
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  boards: { type: Array, required: true },
});

function headerCor(cor) {
  const map = {
    sky:     'bg-sky-50 text-sky-800 dark:bg-sky-900/30 dark:text-sky-200',
    blue:    'bg-blue-50 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200',
    purple:  'bg-purple-50 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200',
    amber:   'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200',
    rose:    'bg-rose-50 text-rose-800 dark:bg-rose-900/30 dark:text-rose-200',
    emerald: 'bg-emerald-50 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-200',
    slate:   'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  };
  return map[cor] ?? map.slate;
}

function fmtDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('pt-BR');
}
</script>
