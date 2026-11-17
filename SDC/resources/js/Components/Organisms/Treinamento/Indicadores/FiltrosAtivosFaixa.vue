<template>
  <div v-if="chips.length > 0" class="flex flex-wrap items-center gap-2" role="group" aria-label="Filtros ativos">
    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Filtros ativos</span>

    <span
      v-for="chip in chips"
      :key="chip.chave"
      class="inline-flex max-w-full items-center gap-1 rounded-full bg-blue-50 py-1 pl-3 pr-1 text-xs text-blue-800 ring-1 ring-blue-200 dark:bg-blue-500/10 dark:text-blue-200 dark:ring-blue-500/25"
    >
      <span class="min-w-0 truncate" :title="`${chip.rotulo}: ${chip.texto}`">{{ chip.rotulo }}: {{ chip.texto }}</span>
      <button
        type="button"
        class="shrink-0 rounded-full p-0.5 hover:bg-blue-100 dark:hover:bg-blue-500/20"
        :aria-label="`Remover filtro ${chip.rotulo}`"
        @click="$emit('remover', chip.chave)"
      >
        <XMarkIcon class="h-3.5 w-3.5" />
      </button>
    </span>

    <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-400" @click="$emit('limpar')">
      Limpar filtros
    </button>
  </div>
</template>

<script setup>
/**
 * Faixa de filtros ativos logo abaixo do cabecalho: os filtros valem para a
 * pagina toda, entao nao podem ficar so escondidos nos seletores dos paineis.
 * O texto de cada chip vem de useFiltrosAtivos (id vira nome pelas `opcoes`).
 */
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import { useFiltrosAtivos } from '@/Composables/treinamento/useFiltrosAtivos';

const props = defineProps({
  filtros: { type: Object, required: true },
  opcoes: { type: Object, required: true },
});

defineEmits(['remover', 'limpar']);

const chips = useFiltrosAtivos(() => props.filtros, () => props.opcoes);
</script>
