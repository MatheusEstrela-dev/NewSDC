<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Pontos de encontro (Critério 1)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Atende quando a população estimada por m² é menor que 3.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar ponto</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Local</th><th class="px-2 py-2">Endereço</th><th class="px-2 py-2">População</th><th class="px-2 py-2">Área (m²)</th><th class="px-2 py-2">Pessoas/m²</th><th class="px-2 py-2">&lt; 3?</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(ponto, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="ponto.nome" :aria-label="`Local do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="255" :class="CLASSE_CAMPO" /><InputError :message="erros[`pontos_encontro.${i}.nome`]" /></td>
            <td class="px-2 py-2"><input v-model.trim="ponto.endereco" :aria-label="`Endereço do ponto de encontro ${i + 1}`" :disabled="somenteLeitura" maxlength="500" :class="CLASSE_CAMPO" /><InputError :message="erros[`pontos_encontro.${i}.endereco`]" /></td>
            <td class="px-2 py-2"><input v-model.number="ponto.populacao" :aria-label="`População do ponto de encontro ${i + 1}`" type="number" min="0" step="1" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'min-w-[6rem]']" /><InputError :message="erros[`pontos_encontro.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><input v-model.number="ponto.area" :aria-label="`Área do ponto de encontro ${i + 1}`" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'min-w-[7rem]']" /><InputError :message="erros[`pontos_encontro.${i}.area`]" /></td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(resultado?.pontos_encontro?.[i]?.densidade) }}</td>
            <td class="px-2 py-2">
              <span v-if="resultado?.pontos_encontro?.[i]" :class="resultado.pontos_encontro[i].conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ resultado.pontos_encontro[i].conforme ? 'Sim' : 'Não' }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.pontos_encontro" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, numero } from '@/utils/paeEvacuacao';

defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);
</script>
