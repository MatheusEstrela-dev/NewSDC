<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';

/*
 * Select pesquisavel de um curso do catalogo (so os ativos chegam aqui), com
 * atalho "Novo curso" que o pai trata abrindo o modal de criacao rapida.
 * v-model recebe o id (ou null).
 */
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  cursos: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Digite para buscar o curso...' },
});
const emit = defineEmits(['update:modelValue', 'novo']);

const busca = ref('');
const listaAberta = ref(false);
const caixa = ref(null);

const semAcento = (v) => (v ?? '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

const selecionado = computed(
  () => props.cursos.find((c) => c.id === Number(props.modelValue)) ?? null,
);

const filtrados = computed(() => {
  const termo = semAcento(busca.value).trim();
  if (!termo) return props.cursos.slice(0, 50);
  return props.cursos
    .filter((c) => semAcento(`${c.nome} ${c.sigla ?? ''}`).includes(termo))
    .slice(0, 50);
});

function escolher(curso) {
  emit('update:modelValue', curso.id);
  busca.value = '';
  listaAberta.value = false;
}

function limpar() {
  emit('update:modelValue', null);
}

function novo() {
  listaAberta.value = false;
  emit('novo');
}

function fecharAoClicarFora(e) {
  if (caixa.value && !caixa.value.contains(e.target)) listaAberta.value = false;
}

onMounted(() => document.addEventListener('mousedown', fecharAoClicarFora));
onBeforeUnmount(() => document.removeEventListener('mousedown', fecharAoClicarFora));
</script>

<template>
  <div ref="caixa" class="relative">
    <div
      v-if="selecionado"
      class="flex items-center justify-between gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
    >
      <span class="truncate">{{ selecionado.nome }}</span>
      <button
        type="button"
        class="min-h-[44px] shrink-0 px-2 text-xs text-slate-500 hover:text-red-600 dark:text-slate-400"
        aria-label="Remover curso"
        @click="limpar"
      >
        Trocar
      </button>
    </div>

    <template v-else>
      <TextInput
        v-model="busca"
        type="search"
        class="block w-full text-sm"
        :placeholder="placeholder"
        autocomplete="off"
        @input="listaAberta = true"
        @click="listaAberta = true"
        @keydown.esc="listaAberta = false"
      />
      <ul
        v-if="listaAberta"
        class="absolute z-20 mt-1 max-h-60 w-full overflow-y-auto rounded-md border border-slate-300 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
      >
        <li v-for="c in filtrados" :key="c.id">
          <button
            type="button"
            class="min-h-[44px] w-full rounded px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
            @click="escolher(c)"
          >
            {{ c.nome }}<span v-if="c.sigla" class="ml-1 text-xs text-slate-400">({{ c.sigla }})</span>
          </button>
        </li>
        <li v-if="filtrados.length === 0" class="px-2 py-2 text-sm text-slate-400">
          Nenhum curso encontrado.
        </li>
        <li class="border-t border-slate-200 pt-1 dark:border-slate-700">
          <button
            type="button"
            class="min-h-[44px] w-full rounded px-2 py-1.5 text-left text-sm font-medium text-blue-600 hover:bg-slate-100 dark:text-blue-400 dark:hover:bg-slate-800"
            @click="novo"
          >
            + Novo curso
          </button>
        </li>
      </ul>
    </template>
  </div>
</template>
