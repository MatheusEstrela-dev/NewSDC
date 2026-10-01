<template>
  <!-- Mesmo corte do Catalogo: tabela a partir de lg, cartao abaixo disso. -->
  <div v-if="isDesktop" :class="{ [MOLDURA]: !aninhada }">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700/50">
        <thead :class="{ 'bg-slate-50 dark:bg-slate-900/50': !aninhada }">
          <tr>
            <th v-for="coluna in colunas" :key="coluna" scope="col" :class="[TH_BASE, 'text-left']">{{ coluna }}</th>
            <th v-if="rotuloAcoes" scope="col" :class="[TH_BASE, 'text-right', { 'table-actions-head': acoesFixas }]">{{ rotuloAcoes }}</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
          <!-- O slot entrega o <tr> inteiro: a linha pode vir com uma segunda (detalhe expandido). -->
          <template v-for="item in itens" :key="chave(item)">
            <slot name="linha" :item="item" :td="TD" :td-forte="TD_FORTE" :total-colunas="totalColunas" />
          </template>
          <tr v-if="vazio && itens.length === 0">
            <td :colspan="totalColunas" class="p-0">
              <ListEmptyState :title="vazio.titulo" :helper="vazio.ajuda" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <component :is="aninhada ? 'ul' : 'div'" v-else :class="aninhada ? 'space-y-2' : 'space-y-3'">
    <component :is="aninhada ? 'li' : 'article'" v-for="item in itens" :key="chave(item)" :class="aninhada ? CARTAO_ANINHADO : CARTAO">
      <slot name="cartao" :item="item" />
    </component>
    <div v-if="vazio && itens.length === 0" :class="MOLDURA">
      <ListEmptyState :title="vazio.titulo" :helper="vazio.ajuda" />
    </div>
  </component>
</template>

<script setup>
import { computed } from 'vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useMobile } from '@/Composables/useMobile';

/**
 * Casca das listas do Inventario: tabela no desktop, cartoes no mobile.
 * Quem usa so desenha a linha (slot `linha`, que recebe as classes de celula)
 * e o conteudo do cartao (slot `cartao`).
 */
const props = defineProps({
  itens: { type: Array, required: true },
  colunas: { type: Array, required: true },
  // Rotulo da coluna de acoes; vazio = sem coluna.
  rotuloAcoes: { type: String, default: '' },
  // Coluna de acoes fixa a direita (utilitario table-sticky).
  acoesFixas: { type: Boolean, default: false },
  // { titulo, ajuda }; nulo = lista que nunca fica vazia.
  vazio: { type: Object, default: null },
  // Lista dentro de outra (itens do lote): sem moldura e cartao compacto.
  aninhada: { type: Boolean, default: false },
  chave: { type: Function, default: (item) => item.id },
});

const { isDesktop } = useMobile();

const totalColunas = computed(() => props.colunas.length + (props.rotuloAcoes ? 1 : 0));

const MOLDURA = 'overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60';
const CARTAO = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60';
const CARTAO_ANINHADO = 'rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700/50 dark:bg-slate-900/60';
const TH_BASE = 'px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400';
const TD = 'whitespace-nowrap px-3 py-2 text-sm text-slate-700 dark:text-slate-200';
const TD_FORTE = 'whitespace-nowrap px-3 py-2 text-sm font-semibold text-slate-900 dark:text-slate-100';
</script>
