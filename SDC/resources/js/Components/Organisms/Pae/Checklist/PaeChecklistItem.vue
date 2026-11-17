<template>
  <div class="min-w-0 space-y-3 border-b border-slate-200 py-4 last:border-b-0 dark:border-slate-700" :class="destaque">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
      <div class="min-w-0 space-y-1">
        <p class="text-sm text-slate-800 dark:text-slate-100">
          <span class="mr-2 font-mono text-xs font-semibold text-slate-500 dark:text-slate-400">{{ item.codigo }}</span>{{ item.texto }}
        </p>
        <p class="break-words text-xs text-slate-500 dark:text-slate-400">Base: {{ item.base }}</p>
      </div>
      <span v-if="gravidade" class="shrink-0 self-start rounded-md px-2 py-0.5 text-xs font-semibold" :class="gravidade.classe" :title="item.artigos || ''">{{ gravidade.rotulo }}</span>
    </div>

    <div v-if="item.tipo !== 'texto'" class="flex flex-wrap items-center gap-2">
      <div v-if="item.tipo === 'resposta'" class="inline-flex overflow-hidden rounded-lg border border-slate-300 dark:border-slate-600" role="radiogroup" :aria-label="`Resposta do item ${item.codigo}`">
        <button
          v-for="opcao in OPCOES"
          :key="opcao.valor"
          type="button"
          role="radio"
          :aria-checked="modelValue.resposta === opcao.valor"
          :disabled="somenteLeitura"
          class="px-3 py-1.5 text-sm font-medium transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 disabled:cursor-not-allowed [&:not(:first-child)]:border-l [&:not(:first-child)]:border-slate-300 dark:[&:not(:first-child)]:border-slate-600"
          :class="modelValue.resposta === opcao.valor ? opcao.ativo : 'bg-white text-slate-600 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'"
          @click="alternarResposta(opcao.valor)"
        >{{ opcao.rotulo }}</button>
      </div>
      <label v-else class="inline-flex cursor-pointer items-center gap-2 text-sm text-slate-700 dark:text-slate-200">
        <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-900" :checked="modelValue.resposta === 'sim'" :disabled="somenteLeitura" @change="emitir({ resposta: $event.target.checked ? 'sim' : null })">
        Marcar esta conclusão
      </label>

      <button v-if="!observacaoAberta && !somenteLeitura" type="button" class="text-sm font-medium text-blue-700 hover:underline dark:text-blue-300" @click="abrirObservacao = true">
        {{ modelValue.resposta === 'nao' ? 'Descrever pendência' : 'Adicionar observação' }}
      </button>
    </div>

    <FormTextarea
      v-if="observacaoAberta"
      :model-value="modelValue.observacao"
      :label="rotuloObservacao"
      :rows="item.tipo === 'texto' ? 5 : 2"
      :maxlength="5000"
      :disabled="somenteLeitura"
      :placeholder="modelValue.resposta === 'nao' ? 'O que precisa ser corrigido ou complementado pelo empreendedor' : ''"
      @update:model-value="emitir({ observacao: $event })"
    />

    <p v-if="erro" class="text-xs text-red-600 dark:text-red-300">{{ erro }}</p>
    <p v-if="item.atualizado_por && !alterado" class="text-xs text-slate-400 dark:text-slate-500">{{ item.atualizado_por }} · {{ formatarDataHora(item.atualizado_em) }}</p>
  </div>
</template>

<script setup>
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { computed, ref } from 'vue';
import { formatarDataHora } from '@/utils/paeTela';

const props = defineProps({
  item: { type: Object, required: true },
  modelValue: { type: Object, required: true },
  somenteLeitura: { type: Boolean, default: false },
  alterado: { type: Boolean, default: false },
  erro: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const OPCOES = [
  { valor: 'sim', rotulo: 'Sim', ativo: 'bg-green-600 text-white dark:bg-green-600' },
  { valor: 'nao', rotulo: 'Não', ativo: 'bg-red-600 text-white dark:bg-red-600' },
  { valor: 'nao_aplicavel', rotulo: 'Não se aplica', ativo: 'bg-slate-600 text-white dark:bg-slate-500' },
];

const GRAVIDADES = {
  sumaria: { rotulo: 'Reprovação sumária', classe: 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300' },
  reprovacao: { rotulo: 'Reprovação', classe: 'bg-orange-100 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300' },
  ccpae: { rotulo: 'Bloqueia CCPAE', classe: 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' },
};

const abrirObservacao = ref(false);
const gravidade = computed(() => {
  const g = GRAVIDADES[props.item.gravidade];
  return g ? { ...g, rotulo: props.item.artigos ? `${g.rotulo} · ${props.item.artigos}` : g.rotulo } : null;
});
const observacaoAberta = computed(() => props.item.tipo === 'texto'
  || abrirObservacao.value
  || props.modelValue.resposta === 'nao'
  || !!props.modelValue.observacao);
const rotuloObservacao = computed(() => {
  if (props.item.tipo === 'texto') return props.item.texto;
  return props.modelValue.resposta === 'nao' ? 'Pendência' : 'Observação';
});
const destaque = computed(() => (props.modelValue.resposta === 'nao' ? 'bg-red-50/60 -mx-4 px-4 dark:bg-red-500/5' : ''));

function emitir(parcial) {
  emit('update:modelValue', { ...props.modelValue, ...parcial });
}

// Clicar de novo na resposta marcada limpa o item.
function alternarResposta(valor) {
  emitir({ resposta: props.modelValue.resposta === valor ? null : valor });
}
</script>
