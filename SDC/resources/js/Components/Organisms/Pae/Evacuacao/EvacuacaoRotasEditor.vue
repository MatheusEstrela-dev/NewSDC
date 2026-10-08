<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Rotas de fuga (Critério 2)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Setores na ordem do percurso, separados por vírgula. Disponíveis: {{ setoresDisponiveis.join(', ') || 'nenhum' }}.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar rota</button>
    </header>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr>
            <th class="px-2 py-2">Rota</th><th class="px-2 py-2">Setores</th><th class="px-2 py-2">Chegada da onda (mm:ss)</th>
            <th class="px-2 py-2">Nível</th><th class="px-2 py-2">TERF</th><th class="px-2 py-2">Saída</th><th class="px-2 py-2">Saída &lt; onda?</th><th class="px-2 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(rota, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="rota.id" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'w-16']" /><InputError :message="erros[`rotas.${i}.id`]" /></td>
            <td class="px-2 py-2">
              <input :value="rota.setores.join(', ')" :disabled="somenteLeitura" placeholder="A, D, E" :class="[CLASSE_CAMPO, 'w-32']" @change="rota.setores = separar($event.target.value)" />
              <InputError :message="erros[`rotas.${i}.setores`]" />
            </td>
            <td class="px-2 py-2"><input v-model.trim="rota.chegada_onda" :disabled="somenteLeitura" placeholder="15:00" maxlength="6" :class="[CLASSE_CAMPO, 'w-20']" /><InputError :message="erros[`rotas.${i}.chegada_onda`]" /></td>
            <td class="px-2 py-2">
              <select v-model.number="rota.nivel_emergencia" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option :value="1">1</option><option :value="2">2</option><option :value="3">3</option></select>
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.terf_fmt ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(rota)?.saida_fmt ?? '—' }}</td>
            <td class="px-2 py-2">
              <span v-if="calculo(rota)" :class="calculo(rota).conforme ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'">{{ calculo(rota).conforme ? 'Sim' : 'Não' }}</span>
              <span v-if="calculo(rota)?.motivo" class="block text-xs text-red-700 dark:text-red-300">{{ MOTIVOS_ROTA[calculo(rota).motivo] }}</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura && itens.length > 1" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
    <InputError class="mt-2" :message="erros.rotas" />
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO, MOTIVOS_ROTA } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  setoresDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (rota) => props.resultado?.rotas?.[rota.id] ?? null;
const separar = (texto) => texto.split(',').map((parte) => parte.trim()).filter(Boolean);
</script>
