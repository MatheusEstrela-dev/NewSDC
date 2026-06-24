<template>
  <section class="space-y-4 rounded-lg border border-gray-200 bg-white p-4">
    <header class="flex items-center justify-between">
      <h2 class="text-base font-semibold text-gray-800">Comunidades e Representantes</h2>
      <span class="text-xs text-gray-500">{{ comunidades.length }} comunidade(s)</span>
    </header>

    <!-- Adicionar comunidade -->
    <form class="flex flex-wrap items-end gap-2" @submit.prevent="adicionarComunidade">
      <div class="flex-1 min-w-[180px]">
        <label class="mb-1 block text-xs text-gray-600">Nome da comunidade</label>
        <input v-model="formComunidade.nome" type="text" maxlength="150" class="w-full rounded-md border-gray-300 text-sm" />
      </div>
      <div class="w-32">
        <label class="mb-1 block text-xs text-gray-600">ID comunidade</label>
        <input v-model="formComunidade.comunidade_id" type="number" class="w-full rounded-md border-gray-300 text-sm" />
      </div>
      <button type="submit" :disabled="formComunidade.processing" class="rounded-md bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-50">
        Adicionar
      </button>
    </form>

    <p v-if="!comunidades.length" class="py-4 text-center text-sm text-gray-400">
      Nenhuma comunidade. Cada comunidade precisa de {{ minRepresentantes }} representantes para o PMDA ficar Completo.
    </p>

    <!-- Lista de comunidades -->
    <div v-for="com in comunidades" :key="com.id" class="rounded-md border border-gray-100 p-3">
      <div class="flex items-center justify-between">
        <div class="font-medium text-gray-800">{{ com.nome ?? `Comunidade #${com.comunidade_id}` }}</div>
        <div class="flex items-center gap-3">
          <span
            class="rounded-full px-2 py-0.5 text-xs font-medium"
            :class="repsCount(com) >= minRepresentantes ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
          >
            {{ repsCount(com) }}/{{ minRepresentantes }} representantes
          </span>
          <button type="button" class="text-xs text-red-600 hover:underline" @click="removerComunidade(com.id)">
            Remover
          </button>
        </div>
      </div>

      <ul class="mt-2 space-y-1">
        <li v-for="rep in (com.representantes ?? [])" :key="rep.id" class="flex items-center justify-between text-sm text-gray-600">
          <span>{{ rep.nome }}<span v-if="rep.tel" class="text-gray-400"> — {{ rep.tel }}</span></span>
          <button type="button" class="text-xs text-red-500 hover:underline" @click="removerRepresentante(rep.id)">remover</button>
        </li>
      </ul>

      <!-- Adicionar representante -->
      <form class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="adicionarRepresentante(com.id)">
        <input
          v-model="formsRepresentante[com.id].nome"
          type="text"
          maxlength="100"
          placeholder="Nome do representante"
          class="flex-1 min-w-[160px] rounded-md border-gray-300 text-sm"
        />
        <input
          v-model="formsRepresentante[com.id].tel"
          type="text"
          maxlength="20"
          placeholder="Telefone"
          class="w-36 rounded-md border-gray-300 text-sm"
        />
        <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
          + Representante
        </button>
      </form>
    </div>
  </section>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  planoId: { type: [Number, String], required: true },
  comunidades: { type: Array, default: () => [] },
  minRepresentantes: { type: Number, default: 3 },
});

const formComunidade = useForm({ nome: '', comunidade_id: '' });

// Um form de representante por comunidade (chaveado pelo id).
const formsRepresentante = reactive({});
function ensureForms(lista) {
  lista.forEach((c) => {
    if (!formsRepresentante[c.id]) {
      formsRepresentante[c.id] = useForm({ nome: '', tel: '' });
    }
  });
}
ensureForms(props.comunidades);
watch(() => props.comunidades, (lista) => ensureForms(lista ?? []), { deep: true });

function repsCount(com) {
  return (com.representantes ?? []).length;
}

function adicionarComunidade() {
  formComunidade.post(route('pmda.planos.comunidades.store', props.planoId), {
    preserveScroll: true,
    onSuccess: () => formComunidade.reset(),
  });
}

function removerComunidade(id) {
  router.delete(route('pmda.comunidades.destroy', id), { preserveScroll: true });
}

function adicionarRepresentante(comunidadeId) {
  formsRepresentante[comunidadeId].post(route('pmda.representantes.store', comunidadeId), {
    preserveScroll: true,
    onSuccess: () => formsRepresentante[comunidadeId].reset(),
  });
}

function removerRepresentante(id) {
  router.delete(route('pmda.representantes.destroy', id), { preserveScroll: true });
}
</script>
