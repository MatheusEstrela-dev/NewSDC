<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';

/*
 * Select pesquisavel de um unico municipio. O catalogo de MG tem 853 itens,
 * entao a lista e filtrada por busca (sem acento/caixa), no mesmo estilo do
 * seletor de municipios do TDAP. v-model recebe o id (ou null).
 */
const props = defineProps({
  modelValue: { type: [Number, String], default: null },
  municipios: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Digite para buscar o município...' },
});
const emit = defineEmits(['update:modelValue']);

const busca = ref('');
const listaAberta = ref(false);
const caixa = ref(null);

const semAcento = (v) => (v ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

const selecionado = computed(
  () => props.municipios.find((m) => m.id === Number(props.modelValue)) ?? null,
);

const filtrados = computed(() => {
  const termo = semAcento(busca.value).trim();
  if (!termo) return props.municipios.slice(0, 50);
  return props.municipios.filter((m) => semAcento(m.nome).includes(termo)).slice(0, 50);
});

function escolher(municipio) {
  emit('update:modelValue', municipio.id);
  busca.value = '';
  listaAberta.value = false;
}

function limpar() {
  emit('update:modelValue', null);
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
        class="shrink-0 text-xs text-slate-500 hover:text-red-600 dark:text-slate-400"
        aria-label="Remover município"
        @click="limpar"
      >
        Remover
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
        class="absolute z-20 mt-1 max-h-52 w-full overflow-y-auto rounded-md border border-slate-300 bg-white p-1 shadow-lg dark:border-slate-700 dark:bg-slate-900"
      >
        <li v-for="m in filtrados" :key="m.id">
          <button
            type="button"
            class="w-full rounded px-2 py-1.5 text-left text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
            @click="escolher(m)"
          >
            {{ m.nome }}
          </button>
        </li>
        <li v-if="filtrados.length === 0" class="px-2 py-2 text-sm text-slate-400">
          Nenhum município encontrado.
        </li>
      </ul>
    </template>
  </div>
</template>
