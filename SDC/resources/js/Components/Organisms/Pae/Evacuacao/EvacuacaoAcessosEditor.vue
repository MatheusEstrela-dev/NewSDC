<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
    <header class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">Acessos à área segura (estrangulamento)</h2>
        <p class="text-sm text-slate-600 dark:text-slate-300">Largura do ponto de maior afunilamento. Abaixo de 1,2 m a rota não pode ser usada (art. 48, §6º). Rotas: {{ rotasDisponiveis.join(', ') || 'nenhuma' }}.</p>
      </div>
      <button v-if="!somenteLeitura" type="button" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 dark:border-slate-600 dark:text-slate-200" @click="$emit('adicionar')">Adicionar acesso</button>
    </header>
    <p v-if="!itens.length" class="mt-4 text-sm text-slate-500 dark:text-slate-400">Sem acesso informado, o tempo total é o tempo máximo de deslocamento.</p>
    <div v-else class="mt-4 overflow-x-auto">
      <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase text-slate-500 dark:text-slate-400">
          <tr><th class="px-2 py-2">Acesso</th><th class="px-2 py-2">Largura (m)</th><th class="px-2 py-2">Terreno</th><th class="px-2 py-2">Rotas</th><th class="px-2 py-2">Pessoas</th><th class="px-2 py-2">TE</th><th class="px-2 py-2"></th></tr>
        </thead>
        <tbody>
          <tr v-for="(acesso, i) in itens" :key="i" class="border-t border-slate-100 align-top dark:border-slate-800">
            <td class="px-2 py-2"><input v-model.trim="acesso.id" :disabled="somenteLeitura" maxlength="10" :class="[CLASSE_CAMPO, 'w-16']" /><InputError :message="erros[`acessos.${i}.id`]" /></td>
            <td class="px-2 py-2"><input v-model.number="acesso.largura" type="number" min="0.01" step="0.01" :disabled="somenteLeitura" :class="[CLASSE_CAMPO, 'w-20']" /><InputError :message="erros[`acessos.${i}.largura`]" /></td>
            <td class="px-2 py-2">
              <select v-model="acesso.terreno" :disabled="somenteLeitura" :class="CLASSE_CAMPO"><option value="plano">Plano</option><option value="inclinado">Rampa ou escada</option></select>
            </td>
            <td class="px-2 py-2">
              <input :value="acesso.rotas.join(', ')" :disabled="somenteLeitura" placeholder="R1, R2" :class="[CLASSE_CAMPO, 'w-28']" @change="acesso.rotas = separar($event.target.value)" />
              <InputError :message="erros[`acessos.${i}.rotas`]" />
            </td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ calculo(acesso)?.n ?? '—' }}</td>
            <td class="px-2 py-2 text-slate-700 dark:text-slate-200">
              {{ calculo(acesso)?.te_fmt ?? '—' }}
              <span v-if="calculo(acesso)?.invalido" class="block text-xs text-red-700 dark:text-red-300">abaixo de 1,2 m</span>
            </td>
            <td class="px-2 py-2"><button v-if="!somenteLeitura" type="button" class="text-sm text-red-700 dark:text-red-300" @click="$emit('remover', i)">Remover</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

<script setup>
import InputError from '@/Components/InputError.vue';
import { CLASSE_CAMPO } from '@/utils/paeEvacuacao';

const props = defineProps({
  itens: { type: Array, required: true },
  erros: { type: Object, required: true },
  resultado: { type: Object, default: null },
  rotasDisponiveis: { type: Array, required: true },
  somenteLeitura: { type: Boolean, default: false },
});

defineEmits(['adicionar', 'remover']);

const calculo = (acesso) => props.resultado?.acessos?.[acesso.id] ?? null;
const separar = (texto) => texto.split(',').map((parte) => parte.trim()).filter(Boolean);
</script>
