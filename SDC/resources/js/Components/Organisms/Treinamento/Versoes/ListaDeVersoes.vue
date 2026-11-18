<template>
  <section aria-label="Versões do curso" class="space-y-2">
    <Heading :level="3" class="text-lg font-semibold">Versões do curso</Heading>

    <ul class="space-y-1">
      <li v-for="versao in versoes" :key="versao.id">
        <Link
          :href="route('treinamentos.show', versao.id)"
          class="flex min-h-[2.5rem] w-full flex-wrap items-center gap-x-2 gap-y-1 rounded-md px-2 py-1.5 text-xs transition-colors"
          :class="versao.atual
            ? 'bg-blue-100/70 text-blue-800 dark:bg-blue-500/15 dark:text-blue-200'
            : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800/60'"
          :aria-current="versao.atual ? 'page' : undefined"
        >
          <DocumentIcon class="h-3.5 w-3.5 shrink-0" />
          <span class="font-semibold">{{ versao.rotulo }}</span>
          <span
            v-if="versao.vigente"
            class="rounded bg-emerald-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300"
          >
            Vigente
          </span>
          <span
            v-else-if="!versao.publicada"
            class="rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase text-amber-700 dark:bg-amber-500/20 dark:text-amber-300"
          >
            Rascunho
          </span>
          <span class="tabular-nums text-slate-500 dark:text-slate-400">
            {{ versao.inscritos }} inscritos · {{ versao.formados }} formados
          </span>
          <span class="ml-auto shrink-0 tabular-nums text-slate-400 dark:text-slate-500">
            {{ dataCurta(versao.publicada_em) }}
          </span>
        </Link>
      </li>
    </ul>
  </section>
</template>

<script setup>
/**
 * Versoes de um curso EAD, da mais nova para a mais antiga, no visual da lista
 * de versoes das Notas Tecnicas. Cada linha leva ao treinamento daquela versao.
 */
import { Link } from '@inertiajs/vue3';
import { DocumentIcon } from '@heroicons/vue/24/outline';
import Heading from '@/Components/Atoms/Typography/Heading.vue';

defineProps({
  versoes: { type: Array, default: () => [] },
});

function dataCurta(iso) {
  return iso ? new Date(iso).toLocaleDateString('pt-BR') : '';
}
</script>
