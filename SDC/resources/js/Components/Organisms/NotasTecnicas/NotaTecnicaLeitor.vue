<template>
  <section aria-label="Leitor de PDF" class="flex h-full min-h-0 flex-col">
    <header
      v-if="nota?.versao"
      class="flex shrink-0 flex-wrap items-center gap-2 border-b border-slate-200 px-3 py-2 dark:border-slate-700/50"
    >
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-slate-900 dark:text-slate-100">
          {{ nota.codigo }} · {{ nota.titulo }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Versão {{ nota.versao.numero }}
          <span v-if="!nota.versao.atual" class="font-semibold text-amber-600 dark:text-amber-400">· versão anterior, não vigente</span>
        </p>
      </div>
      <div class="flex shrink-0 items-center gap-1">
        <a
          :href="nota.versao.pdf_url"
          target="_blank"
          rel="noopener"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
          title="Abrir em outra aba"
          aria-label="Abrir PDF em outra aba"
        >
          <ArrowTopRightOnSquareIcon class="h-5 w-5" />
        </a>
        <a
          :href="nota.versao.download_url"
          class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200"
          title="Baixar PDF"
          aria-label="Baixar PDF"
        >
          <ArrowDownTrayIcon class="h-5 w-5" />
        </a>
      </div>
    </header>

    <!--
      Leitor nativo do navegador (sumario, zoom, busca, impressao). :key na URL:
      trocar de versao recria o iframe em vez de deixar o PDF anterior na tela
      enquanto o novo carrega.
    -->
    <iframe
      v-if="nota?.versao && embutir"
      :key="nota.versao.pdf_url"
      :src="nota.versao.pdf_url"
      :title="`PDF de ${nota.codigo}`"
      class="min-h-0 w-full flex-1 bg-slate-100 dark:bg-slate-950"
    />

    <!--
      Telefone nao renderiza PDF dentro de iframe (regra 14 da responsividade):
      o documento sai do layout do app e abre na aba propria, com zoom do aparelho.
    -->
    <div v-else-if="nota?.versao" class="flex flex-1 flex-col items-center justify-center gap-3 p-6 text-center">
      <DocumentTextIcon class="h-10 w-10 text-slate-400" />
      <a
        :href="nota.versao.pdf_url"
        target="_blank"
        rel="noopener"
        class="inline-flex min-h-[2.5rem] items-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700"
      >
        <ArrowTopRightOnSquareIcon class="h-4 w-4" />
        Abrir PDF
      </a>
    </div>

    <div v-else-if="nota" class="flex flex-1 items-center justify-center p-6">
      <ListEmptyState :icon="DocumentTextIcon" title="Nota sem PDF" helper="Esta nota ainda não tem nenhuma versão enviada." />
    </div>

    <div v-else class="flex flex-1 items-center justify-center p-6">
      <ListEmptyState
        :icon="DocumentTextIcon"
        title="Selecione uma nota"
        helper="O PDF abre aqui, com sumário, zoom, busca e impressão."
      />
    </div>
  </section>
</template>

<script setup>
/**
 * Painel central-direito: o PDF da versao aberta. No desktop, embutido via
 * iframe na rota que serve o arquivo inline; no telefone, um botao que abre o
 * PDF na aba propria.
 */
import { ArrowDownTrayIcon, ArrowTopRightOnSquareIcon, DocumentTextIcon } from '@heroicons/vue/24/outline';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

defineProps({
  nota: { type: Object, default: null },
  embutir: { type: Boolean, default: true },
});
</script>
