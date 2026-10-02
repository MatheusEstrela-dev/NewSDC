<template>
  <div class="min-w-0">
    <label :for="campoId" class="text-xs font-medium text-slate-500 dark:text-slate-400">Adicionar outro equipamento</label>
    <input
      :id="campoId"
      v-model="termo"
      type="search"
      autocomplete="off"
      placeholder="Patrimônio ou nome (mínimo 2 letras)"
      class="mt-1 w-full min-w-0 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-orange-500 focus:ring-orange-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
    />
    <ul
      v-if="resultados.length"
      class="mt-1 max-h-56 divide-y divide-slate-200 overflow-y-auto rounded-lg border border-slate-200 bg-white dark:divide-slate-700/50 dark:border-slate-700/50 dark:bg-slate-900"
    >
      <li v-for="equipamento in resultados" :key="equipamento.id">
        <button
          type="button"
          class="flex w-full min-w-0 items-center justify-between gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50 dark:text-slate-200 dark:hover:bg-slate-800"
          :disabled="Boolean(impedimento(equipamento))"
          @click="adicionar(equipamento)"
        >
          <span class="min-w-0 truncate">{{ equipamento.patrimonio ?? 'Sem patrimônio' }} — {{ equipamento.nome }}</span>
          <span v-if="impedimento(equipamento)" class="shrink-0 text-xs text-amber-600 dark:text-amber-400">{{ impedimento(equipamento) }}</span>
        </button>
      </li>
    </ul>
    <p v-else-if="termo.trim().length >= 2" class="mt-1 text-xs text-slate-500 dark:text-slate-400">Nenhum equipamento encontrado.</p>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const LIMITE_RESULTADOS = 20;

const props = defineProps({
  equipamentos: { type: Array, required: true },
  // Ja aparecem na lista do bloco: nao repetem na busca.
  jaListados: { type: Array, default: () => [] },
  // Marcados em outro bloco do lote: aparecem, mas desabilitados, para o
  // usuario entender por que nao pode adicionar.
  noLote: { type: Array, default: () => [] },
});
const emit = defineEmits(['adicionar']);

const impedimento = (equipamento) => equipamento.bloqueio
  || (props.noLote.includes(equipamento.id) ? 'Já está no lote' : '');

const campoId = `busca-equipamento-${Math.random().toString(36).slice(2, 8)}`;
const termo = ref('');

const resultados = computed(() => {
  const busca = termo.value.trim().toLowerCase();
  if (busca.length < 2) return [];
  return props.equipamentos
    .filter((e) => !props.jaListados.includes(e.id) && `${e.patrimonio ?? ''} ${e.nome}`.toLowerCase().includes(busca))
    .slice(0, LIMITE_RESULTADOS);
});

function adicionar(equipamento) {
  if (impedimento(equipamento)) return;
  emit('adicionar', equipamento.id);
  termo.value = '';
}
</script>
