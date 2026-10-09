<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Setores de evacuação</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Sem calçada, a largura da rua desconta 2,90 m (mão única) ou 5,80 m (mão dupla).</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar setor</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Setor</th><th class="px-2 py-2">Moradores</th><th class="px-2 py-2">Comercial</th>
            <th class="px-2 py-2">Via</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Lados</th>
            <th class="px-2 py-2">Distância (m)</th><th class="px-2 py-2">Terreno</th>
            <th class="px-2 py-2">Densidade</th><th class="px-2 py-2">Velocidade</th><th class="px-2 py-2">Tempo</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(setor, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="setor.id" :aria-label="`Identificador do setor ${i + 1}`" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'min-w-[4rem]']" /><InputError :message="erros[`setores.${i}.id`]" /></td>
            <td class="px-2 py-2"><input v-model.number="setor.populacao" :aria-label="`Moradores do setor ${i + 1}`" type="number" min="0" step="1" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'min-w-[6rem]']" /><InputError :message="erros[`setores.${i}.populacao`]" /></td>
            <td class="px-2 py-2"><input v-model="setor.comercial" :aria-label="`Área comercial do setor ${i + 1}`" type="checkbox" :disabled="somenteLeitura" /></td>
            <td class="px-2 py-2">
              <select v-model="setor.via" :aria-label="`Tipo de via do setor ${i + 1}`" :disabled="somenteLeitura" :class="CLASSE_CAMPO">
                <option value="calcada">Calçada</option><option value="rua_mao_unica">Rua mão única</option><option value="rua_mao_dupla">Rua mão dupla</option>
              </select>
            </td>
            <td class="px-2 py-2"><input v-model.number="setor.largura" :aria-label="`Largura do setor ${i + 1}`" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'min-w-[5rem]']" /><InputError :message="erros[`setores.${i}.largura`]" /></td>
            <td class="px-2 py-2">
              <select v-if="setor.via === 'calcada'" v-model.number="setor.lados" :aria-label="`Lados da calçada do setor ${i + 1}`" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option :value="1">1</option><option :value="2">2</option></select>
              <span v-else class="text-slate-400">—</span>
              <InputError :message="erros[`setores.${i}.lados`]" />
            </td>
            <td class="px-2 py-2"><input v-model.number="setor.distancia" :aria-label="`Distância do setor ${i + 1}`" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'min-w-[6rem]']" /><InputError :message="erros[`setores.${i}.distancia`]" /></td>
            <td class="px-2 py-2">
              <select v-model="setor.terreno" :aria-label="`Terreno do setor ${i + 1}`" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option value="plano">Plano</option><option value="inclinado">Inclinado (&gt; 5%)</option></select>
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.densidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ numero(calculo(setor)?.velocidade) }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(setor)?.tempo_fmt ?? '—' }}
              <span v-if="SITUACOES_SETOR[calculo(setor)?.situacao]" class="block text-xs text-red-700 dark:text-red-300">{{ SITUACOES_SETOR[calculo(setor).situacao] }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.setores" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, SITUACOES_SETOR, numero } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  simulado: { type: Boolean, default: false },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (setor) => props.simulado ? (props.resultado?.setores?.[setor.id] ?? null) : null;
</script>
