<template>
  <CollapsibleSection
    namespace="cisterna"
    section-id="checklist"
    title="Checklist da instalacao"
    subtitle="Os 13 itens do kit conferidos na vistoria"
    :icon="ClipboardDocumentCheckIcon"
    tom="success"
    :status-text="resumo"
  >
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ resumo }}</p>

      <div v-if="!somenteLeitura" class="flex gap-2">
        <button type="button" :class="BOTAO" @click="marcarTodos(true)">Marcar todos</button>
        <button type="button" :class="BOTAO" @click="marcarTodos(false)">Limpar</button>
      </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
      <ItemConferidoRow
        v-for="item in itens"
        :key="item.value"
        :item="item"
        :conferido="linha(item.value).conferido"
        :quantidade="linha(item.value).quantidade"
        :detalhes="linha(item.value).detalhes"
        :observacao="linha(item.value).observacao"
        :somente-leitura="somenteLeitura"
        @update:conferido="atualizar(item.value, 'conferido', $event)"
        @update:quantidade="atualizar(item.value, 'quantidade', $event)"
        @update:detalhes="atualizar(item.value, 'detalhes', $event)"
        @update:observacao="atualizar(item.value, 'observacao', $event)"
      />
    </div>
  </CollapsibleSection>
</template>

<script setup>
import { computed } from 'vue';
import { ClipboardDocumentCheckIcon } from '@heroicons/vue/24/outline';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import ItemConferidoRow from '@/Components/Molecules/Cisterna/ItemConferidoRow.vue';

/**
 * O checklist inteiro. O valor e um objeto indexado pelo valor do item, que e o
 * formato que o StoreVistoriaRequest espera em `itens.*`.
 *
 * No legado eram ~87 colunas espalhadas em tres tabelas, com nome diferente em
 * cada etapa. Aqui e uma estrutura so, e a diferenca entre etapas fica no que o
 * item aceita -- nao no nome da coluna.
 */
const props = defineProps({
  /** ItemInstalacao::options() vindo do controller. */
  itens: { type: Array, default: () => [] },
  /** { [item]: { conferido, quantidade, detalhes, observacao } } */
  modelValue: { type: Object, default: () => ({}) },
  somenteLeitura: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const BOTAO = 'rounded border border-slate-300 px-2 py-1 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800';

const VAZIA = { conferido: false, quantidade: '', detalhes: null, observacao: '' };

const conferidos = computed(
  () => props.itens.filter((i) => props.modelValue?.[i.value]?.conferido === true).length,
);

const resumo = computed(() => `${conferidos.value} de ${props.itens.length} itens conferidos`);

function linha(item) {
  return { ...VAZIA, ...(props.modelValue?.[item] ?? {}) };
}

function atualizar(item, campo, valor) {
  const atual = linha(item);
  const proxima = { ...atual, [campo]: valor };

  // Desmarcar limpa o que dependia da marcacao: quantidade e detalhe de item
  // nao conferido nao significam nada, e o backend gravaria numero orfao.
  if (campo === 'conferido' && valor === false) {
    proxima.quantidade = '';
    proxima.detalhes = null;
    proxima.observacao = '';
  }

  emit('update:modelValue', { ...(props.modelValue ?? {}), [item]: proxima });
}

function marcarTodos(valor) {
  const proximo = {};

  props.itens.forEach((i) => {
    proximo[i.value] = valor
      ? { ...linha(i.value), conferido: true }
      : { ...VAZIA };
  });

  emit('update:modelValue', proximo);
}
</script>
