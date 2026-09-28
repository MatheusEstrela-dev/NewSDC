<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-3 text-lg font-bold text-slate-900 dark:text-slate-100">Descrição</h2>
    <textarea v-model="texto" rows="5" :disabled="!podeEditar"
      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
      @blur="salvarSeMudou" />
    <p v-if="podeEditar" class="mt-2 text-xs text-slate-500">{{ salvando ? 'Salvando…' : 'Clique fora da caixa de texto para salvar as alterações automaticamente.' }}</p>
  </section>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useDemandaAutosave } from '@/Composables/demandas';

const props = defineProps({
  demandaId: { type: Number, required: true },
  descricao: { type: String, default: '' },
  podeEditar: { type: Boolean, default: false },
});

const texto = ref(props.descricao ?? '');
watch(() => props.descricao, (v) => { texto.value = v ?? ''; });
const { salvando, salvar } = useDemandaAutosave(props.demandaId);

function salvarSeMudou() {
  if (texto.value !== (props.descricao ?? '') && texto.value.trim() !== '') salvar({ descricao: texto.value });
}
</script>
