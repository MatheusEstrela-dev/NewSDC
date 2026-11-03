<template>
  <nav aria-label="Pastas de notas técnicas" class="flex h-full min-h-0 flex-col">
    <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-3 py-2.5 dark:border-slate-700/50">
      <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Pastas</h2>
      <slot name="acoes" />
    </header>

    <ul class="min-h-0 flex-1 space-y-0.5 overflow-y-auto p-2">
      <li>
        <button
          type="button"
          class="flex min-h-[2.5rem] w-full items-center gap-2 rounded-lg px-2 text-left text-sm font-medium transition-colors"
          :class="categoriaId === null
            ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
            : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60'"
          :aria-current="categoriaId === null ? 'true' : undefined"
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
        :selecionada-id="categoriaId"
        @select="$emit('select', $event)"
      />
    </ul>
  </nav>
</template>

<script setup>
/**
 * Painel esquerdo do workspace: a arvore de pastas. Emite a pasta escolhida
 * (null = todas); quem filtra a lista e a pagina.
 */
import { computed } from 'vue';
import { InboxStackIcon } from '@heroicons/vue/24/outline';
import NotaTecnicaCategoriaItem from '@/Components/Molecules/NotasTecnicas/NotaTecnicaCategoriaItem.vue';

const props = defineProps({
  categorias: { type: Array, default: () => [] },
  categoriaId: { type: Number, default: null },
});

defineEmits(['select']);

const total = computed(() => props.categorias.reduce((soma, c) => soma + (c.total ?? 0), 0));
</script>
