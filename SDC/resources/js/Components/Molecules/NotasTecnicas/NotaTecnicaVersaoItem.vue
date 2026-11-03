<template>
  <div
    class="flex min-h-[2.5rem] w-full items-center gap-1 rounded-md pr-1 text-xs transition-colors"
    :class="ativa
      ? 'bg-blue-100/70 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200'
      : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800/60'"
  >
    <button
      type="button"
      class="flex min-h-[2.5rem] min-w-0 flex-1 items-center gap-2 px-2 text-left"
      :aria-current="ativa ? 'true' : undefined"
      @click="$emit('select', versao.id)"
    >
      <DocumentIcon class="h-3.5 w-3.5 shrink-0" />
      <span class="font-semibold">v{{ versao.numero }}</span>
      <span v-if="versao.atual" :class="[selo, 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300']">
        Vigente
      </span>
      <span class="ml-auto shrink-0 tabular-nums text-slate-400 dark:text-slate-500">
        {{ dataCurta(versao.publicada_em) }}
      </span>
    </button>

    <!--
      Revogar mora no historico, ao lado do selo Vigente: e a versao em vigor que
      deixa de valer. Visual de selo, mas alvo de toque de 40px (o botao inteiro).
    -->
    <button
      v-if="podeRevogar"
      type="button"
      class="flex h-10 shrink-0 items-center rounded-md px-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
      :title="`Revogar: a nota deixa de estar em vigor`"
      @click="$emit('revogar')"
    >
      <span :class="[selo, 'bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-500/20 dark:text-red-300 dark:hover:bg-red-500/30']">
        Revogar
      </span>
    </button>
  </div>
</template>

<script setup>
/**
 * Uma revisao da nota, listada sob a nota aberta (o "Conversations" do Notes):
 * v2.1 vigente, v2.0, v1.0... Clicar abre aquela versao no leitor; na vigente,
 * quem pode publicar ve o selo Revogar. Molecula burra: so emite.
 */
import { DocumentIcon } from '@heroicons/vue/24/outline';

defineProps({
  versao: { type: Object, required: true },
  ativa: { type: Boolean, default: false },
  podeRevogar: { type: Boolean, default: false },
});

defineEmits(['select', 'revogar']);

const selo = 'rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase';

function dataCurta(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}
</script>
