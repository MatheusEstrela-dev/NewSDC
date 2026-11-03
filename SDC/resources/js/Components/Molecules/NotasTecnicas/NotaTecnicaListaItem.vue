<template>
  <li
    class="rounded-lg border transition-colors"
    :class="ativa
      ? 'border-blue-300 bg-blue-50/60 dark:border-blue-500/40 dark:bg-blue-500/5'
      : 'border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/40'"
  >
    <button
      type="button"
      class="flex w-full min-w-0 items-start gap-2 px-3 py-2.5 text-left"
      :aria-current="ativa ? 'true' : undefined"
      @click="$emit('select', nota.id)"
    >
      <span
        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
        :class="naoLida ? 'bg-blue-500' : 'bg-transparent'"
        :title="naoLida ? 'Versão vigente ainda não lida' : undefined"
        aria-hidden="true"
      />
      <span class="min-w-0 flex-1">
        <span class="flex items-center gap-2">
          <span class="text-xs font-semibold tabular-nums text-slate-500 dark:text-slate-400">{{ nota.codigo }}</span>
          <template v-if="nota.favorita">
            <StarIcon class="h-3.5 w-3.5 shrink-0 text-amber-500" aria-hidden="true" />
            <span class="sr-only">Favorita</span>
          </template>
          <Badge v-if="nota.status !== 'publicada'" :variant="nota.status_variante" size="sm">
            {{ nota.status_label }}
          </Badge>
          <span v-if="naoLida" class="sr-only">Não lida</span>
        </span>
        <span
          class="mt-0.5 block text-sm leading-snug text-slate-900 dark:text-slate-100"
          :class="naoLida ? 'font-semibold' : 'font-medium'"
        >
          {{ nota.titulo }}
        </span>
        <span class="mt-1 flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
          <span class="truncate">{{ nota.categoria }}</span>
          <span v-if="nota.versao_atual" class="shrink-0">· v{{ nota.versao_atual }}</span>
          <span v-if="nota.total_versoes > 1" class="shrink-0">· {{ nota.total_versoes }} versões</span>
        </span>
        <!-- Termo buscado encontrado no texto do PDF: o trecho, com o termo marcado. -->
        <span
          v-if="trecho.length"
          class="mt-1.5 block rounded-md bg-slate-100 px-2 py-1 text-xs leading-relaxed text-slate-600 dark:bg-slate-800/70 dark:text-slate-300"
        >
          <span class="font-semibold text-slate-500 dark:text-slate-400">No PDF: </span>…<template
            v-for="(parte, i) in trecho"
            :key="i"
          ><mark
            v-if="parte.destaque"
            class="rounded bg-amber-200 px-0.5 text-slate-900 dark:bg-amber-400/30 dark:text-amber-100"
          >{{ parte.texto }}</mark><template v-else>{{ parte.texto }}</template></template>…
        </span>
      </span>
    </button>

    <!-- Revisoes da nota aberta, como as conversas agrupadas do Notes. -->
    <ul v-if="ativa && versoes.length > 1" class="space-y-0.5 border-t border-blue-200/70 px-3 py-2 dark:border-blue-500/20">
      <li v-for="versao in versoes" :key="versao.id">
        <NotaTecnicaVersaoItem
          :versao="versao"
          :ativa="versao.id === versaoId"
          @select="$emit('select-versao', $event)"
        />
      </li>
    </ul>
  </li>
</template>

<script setup>
/**
 * Linha da lista de notas: codigo, titulo, pasta e versao vigente. O ponto azul
 * e o titulo em negrito marcam nota cuja versao vigente o usuario nao leu --
 * como e-mail nao lido.
 */
import { computed } from 'vue';
import { StarIcon } from '@heroicons/vue/24/solid';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import NotaTecnicaVersaoItem from '@/Components/Molecules/NotasTecnicas/NotaTecnicaVersaoItem.vue';

const props = defineProps({
  nota: { type: Object, required: true },
  ativa: { type: Boolean, default: false },
  versoes: { type: Array, default: () => [] },
  versaoId: { type: Number, default: null },
});

defineEmits(['select', 'select-versao']);

/**
 * O backend marca o termo com [[ ]] (ts_headline). Partir em pedacos e
 * renderizar como texto evita v-html com conteudo vindo do PDF.
 */
const trecho = computed(() => (props.nota.trecho ?? '')
  .split(/(\[\[.*?\]\])/)
  .filter(Boolean)
  .map((parte) => (parte.startsWith('[[') && parte.endsWith(']]')
    ? { texto: parte.slice(2, -2), destaque: true }
    : { texto: parte.replace(/\s+/g, ' '), destaque: false })));

// Rascunho nao entra na conta de "nao lida": ninguem precisa dar ciencia nele.
const naoLida = computed(() => props.nota.status === 'publicada' && !props.nota.lida);
</script>
