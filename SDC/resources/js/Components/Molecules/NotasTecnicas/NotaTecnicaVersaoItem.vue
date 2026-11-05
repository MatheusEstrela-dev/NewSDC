<template>
  <button
    type="button"
    class="flex min-h-[2.5rem] w-full items-center gap-2 rounded-md px-2 text-left text-xs transition-colors"
    :class="ativa
      ? 'bg-blue-100/70 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200'
      : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800/60'"
    :aria-current="ativa ? 'true' : undefined"
    @click="$emit('select', versao.id)"
  >
    <DocumentIcon class="h-3.5 w-3.5 shrink-0" />
    <span class="font-semibold">v{{ versao.numero }}</span>
    <span v-if="versao.atual" class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
      Vigente
    </span>
    <span class="ml-auto shrink-0 tabular-nums text-slate-400 dark:text-slate-500">
      {{ dataCurta(versao.publicada_em) }}
    </span>
  </button>
</template>

<script setup>
/**
 * Uma revisao da nota, listada sob a nota aberta (o "Conversations" do Notes):
 * v2.1 vigente, v2.0, v1.0... Clicar abre aquela versao no leitor.
 */
import { DocumentIcon } from '@heroicons/vue/24/outline';

defineProps({
  versao: { type: Object, required: true },
  ativa: { type: Boolean, default: false },
});

defineEmits(['select']);

function dataCurta(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}
</script>
