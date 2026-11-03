<template>
  <nav aria-label="Pastas de notas técnicas" class="flex h-full min-h-0 flex-col">
    <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-3 py-2.5 dark:border-slate-700/50">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pastas</h2>
      <slot name="acoes" />
    </header>

    <ul class="min-h-0 flex-1 space-y-0.5 overflow-y-auto p-2">
      <li v-for="item in itensColecao" :key="item.chave">
        <button
          type="button"
          class="flex min-h-[2.5rem] w-full min-w-0 items-center gap-2 rounded-lg px-2 text-left text-sm font-medium transition-colors"
          :class="colecao === item.chave
            ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60'"
          :aria-current="colecao === item.chave ? 'true' : undefined"
          @click="$emit('select-colecao', item.chave)"
        >
          <component :is="item.icone" class="h-4 w-4 shrink-0" />
          <span class="truncate">{{ item.rotulo }}</span>
          <span class="ml-auto shrink-0 text-xs tabular-nums text-slate-400 dark:text-slate-500">{{ item.total }}</span>
        </button>
      </li>

      <!-- Separador discreto: colecoes (atalhos) de um lado, pastas do outro. -->
      <li class="my-1.5 border-t border-slate-200 dark:border-slate-700/50" role="separator" />

      <li>
        <button
          type="button"
          class="flex min-h-[2.5rem] w-full min-w-0 items-center gap-2 rounded-lg px-2 text-left text-sm font-medium transition-colors"
          :class="todasAtivo
            ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60'"
          :aria-current="todasAtivo ? 'true' : undefined"
          @click="$emit('select', null)"
        >
          <InboxStackIcon class="h-4 w-4 shrink-0" />
          <span class="truncate">Todas as notas</span>
          <span class="ml-auto text-xs tabular-nums text-slate-400 dark:text-slate-500">{{ total }}</span>
        </button>
      </li>

      <NotaTecnicaCategoriaItem
        v-for="categoria in categorias"
        :key="categoria.id"
        :categoria="categoria"
        :selecionada-id="colecao ? null : categoriaId"
        @select="$emit('select', $event)"
      />
    </ul>
  </nav>
</template>

<script setup>
/**
 * Painel esquerdo do workspace: colecoes (favoritas/recentes) e a arvore de
 * pastas. Emite a pasta escolhida (null = todas) ou a colecao; quem filtra a
 * lista e a pagina. Colecao e pasta sao eixos exclusivos: com colecao ativa,
 * nem "Todas as notas" nem pasta ficam marcadas.
 */
import { computed } from 'vue';
import { ClockIcon, InboxStackIcon, StarIcon } from '@heroicons/vue/24/outline';
import NotaTecnicaCategoriaItem from '@/Components/Molecules/NotasTecnicas/NotaTecnicaCategoriaItem.vue';

const props = defineProps({
  categorias: { type: Array, default: () => [] },
  categoriaId: { type: Number, default: null },
  colecao: { type: String, default: null },
  colecoes: { type: Object, default: () => ({ favoritas: 0, recentes: 0 }) },
});

defineEmits(['select', 'select-colecao']);

const itensColecao = computed(() => [
  { chave: 'favoritas', rotulo: 'Favoritas', icone: StarIcon, total: props.colecoes?.favoritas ?? 0 },
  { chave: 'recentes', rotulo: 'Recentes', icone: ClockIcon, total: props.colecoes?.recentes ?? 0 },
]);

const todasAtivo = computed(() => !props.colecao && props.categoriaId === null);

const total = computed(() => props.categorias.reduce((soma, c) => soma + (c.total ?? 0), 0));
</script>
