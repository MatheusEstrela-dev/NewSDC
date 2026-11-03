<template>
  <li>
    <div
      class="group flex items-center gap-0.5 rounded-lg pr-2 text-sm transition-colors"
      :class="ativa
        ? 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300'
        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800/60'"
      :style="{ paddingLeft: `${0.125 + nivel * 0.5}rem` }"
    >
      <button
        v-if="temFilhas"
        type="button"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
        :aria-label="aberta ? `Recolher ${categoria.nome}` : `Expandir ${categoria.nome}`"
        :aria-expanded="aberta"
        @click="aberta = !aberta"
      >
        <ChevronRightIcon class="h-4 w-4 transition-transform" :class="{ 'rotate-90': aberta }" />
      </button>
      <span v-else class="w-10 shrink-0" aria-hidden="true" />

      <button
        type="button"
        class="flex min-h-[2.5rem] min-w-0 flex-1 items-center gap-2 text-left"
        :aria-current="ativa ? 'true' : undefined"
        @click="$emit('select', categoria.id)"
      >
        <component :is="ativa || aberta ? FolderOpenIcon : FolderIcon" class="h-4 w-4 shrink-0" />
        <span class="truncate font-medium">{{ categoria.nome }}</span>
        <span class="ml-auto shrink-0 text-xs tabular-nums text-slate-400 dark:text-slate-500">
          {{ categoria.total }}
        </span>
      </button>
    </div>

    <ul v-if="temFilhas && aberta" class="mt-0.5 space-y-0.5">
      <NotaTecnicaCategoriaItem
        v-for="filha in categoria.filhas"
        :key="filha.id"
        :categoria="filha"
        :selecionada-id="selecionadaId"
        :nivel="nivel + 1"
        @select="$emit('select', $event)"
      />
    </ul>
  </li>
</template>

<script setup>
/**
 * No da arvore de pastas. Recursivo: cada subpasta e outro NotaTecnicaCategoriaItem.
 * Molecula burra: recebe a categoria e devolve o clique; quem filtra e a pagina.
 *
 * Nasce aberta quando a pasta selecionada esta dentro dela, para a selecao vinda
 * pela URL nao ficar escondida numa pasta fechada.
 */
import { computed, ref } from 'vue';
import { ChevronRightIcon, FolderIcon, FolderOpenIcon } from '@heroicons/vue/24/outline';

defineOptions({ name: 'NotaTecnicaCategoriaItem' });

const props = defineProps({
  categoria: { type: Object, required: true },
  selecionadaId: { type: Number, default: null },
  nivel: { type: Number, default: 0 },
});

defineEmits(['select']);

const temFilhas = computed(() => (props.categoria.filhas?.length ?? 0) > 0);
const ativa = computed(() => props.categoria.id === props.selecionadaId);

function contemSelecionada(categoria) {
  return (categoria.filhas ?? []).some(
    (filha) => filha.id === props.selecionadaId || contemSelecionada(filha),
  );
}

const aberta = ref(contemSelecionada(props.categoria));
</script>
