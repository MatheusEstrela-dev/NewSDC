<script setup>
/**
 * Cartao "tipo de desastre" da decretacao: nome, COBRADE e a classificacao.
 *
 * Saiu do cabecalho do modal para ca porque no telefone ele ocupava a tela
 * inteira ANTES do conteudo da aba -- e como e informacao basica do processo,
 * so faz sentido junto das demais informacoes basicas. No desktop continua no
 * cabecalho, onde sempre esteve e onde ha largura de sobra.
 *
 * Os tres caminhos de leitura (`tipo_desastre_info`, `tipo_desastre`,
 * `tipo_desastre_*`) vieram como estavam: o payload muda conforme a origem do
 * processo, e nao e este componente que vai unificar isso.
 */
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

defineProps({
  processo: {
    type: Object,
    required: true,
  },
});
</script>

<template>
<div class="p-3 md:p-4 bg-white dark:bg-slate-700/30 rounded-xl border border-slate-200 dark:border-slate-600/30 shadow-sm">
  <div class="flex items-center gap-3">
    <ExclamationTriangleIcon class="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0" />
    <span class="text-slate-800 dark:text-white font-medium">{{ processo.tipo_desastre_info?.nome || processo.tipo_desastre?.nome || processo.tipo_desastre_nome || 'N/A' }}</span>
  </div>
  <!--
    Uma coluna ate 475px. Com `grid-cols-2` na base cada celula ficava
    com ~150px em 375px, e "COBRADE: 1.3.2.1.3" invadia "Categoria:
    N/A" -- rotulo e valor sao spans em linha, entao a celula nao
    segurava o texto.
  -->
  <div class="mt-3 grid grid-cols-1 xs:grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-x-3 gap-y-2 text-sm">
    <div>
      <span class="text-slate-500 dark:text-slate-400">COBRADE:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.cobrade || processo.tipo_desastre?.cobrade || processo.tipo_desastre_cobrade || 'N/A' }}</span>
    </div>
    <div>
      <span class="text-slate-500 dark:text-slate-400">Categoria:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.categoria || processo.tipo_desastre?.categoria || 'N/A' }}</span>
    </div>
    <div>
      <span class="text-slate-500 dark:text-slate-400">Grupo:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.grupo || processo.tipo_desastre?.grupo || 'N/A' }}</span>
    </div>
    <div>
      <span class="text-slate-500 dark:text-slate-400">Subgrupo:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.subgrupo || processo.tipo_desastre?.subgrupo || 'N/A' }}</span>
    </div>
    <div>
      <span class="text-slate-500 dark:text-slate-400">Tipo:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.tipo || processo.tipo_desastre?.tipo || 'N/A' }}</span>
    </div>
    <div>
      <span class="text-slate-500 dark:text-slate-400">Subtipo:</span>
      <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.subtipo || processo.tipo_desastre?.subtipo || 'N/A' }}</span>
    </div>
  </div>
  <div v-if="processo.tipo_desastre_info?.definicao || processo.tipo_desastre?.definicao" class="mt-2 text-sm">
    <span class="text-slate-500 dark:text-slate-400">Definicao:</span>
    <span class="ml-1 text-slate-700 dark:text-slate-200">{{ processo.tipo_desastre_info?.definicao || processo.tipo_desastre?.definicao }}</span>
  </div>
</div>
</template>
