<template>
  <th :class="thClasses" :aria-sort="ariaSort">
    <button
      v-if="coluna"
      type="button"
      class="group inline-flex items-center gap-1 uppercase tracking-wider transition-colors hover:text-cyan-700 focus:outline-none focus-visible:text-cyan-700 dark:hover:text-cyan-300 dark:focus-visible:text-cyan-300"
      :title="titulo"
      @click="alternar"
    >
      <slot />

      <!-- A seta some quando a coluna nao e a ativa, mas o espaco fica: sem
           isso o cabecalho salta lateralmente ao ordenar. -->
      <span class="flex flex-col leading-none" aria-hidden="true">
        <ChevronUpIcon
          class="h-3 w-3"
          :class="ativa && direcao === 'asc'
            ? 'text-cyan-600 dark:text-cyan-400'
            : 'text-slate-300 group-hover:text-slate-400 dark:text-slate-600'"
        />
        <ChevronDownIcon
          class="-mt-1 h-3 w-3"
          :class="ativa && direcao === 'desc'
            ? 'text-cyan-600 dark:text-cyan-400'
            : 'text-slate-300 group-hover:text-slate-400 dark:text-slate-600'"
        />
      </span>
    </button>

    <template v-else>
      <slot />
    </template>
  </th>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/solid';

const props = defineProps({
  /**
   * Nome da coluna no backend. Sem ele o cabecalho e apenas texto, o que cobre
   * colunas derivadas que nao existem como coluna ordenavel (ex.: Acoes).
   */
  coluna: {
    type: String,
    default: '',
  },

  /** Coluna ordenada no momento, vinda da URL. */
  ordenadoPor: {
    type: String,
    default: '',
  },

  /** Direcao atual: 'asc' ou 'desc'. */
  direcao: {
    type: String,
    default: 'desc',
  },

  /**
   * Direcao do primeiro clique. Texto comeca em A-Z; data comeca da mais
   * recente, que e o que se espera ao abrir uma listagem cronologica.
   */
  direcaoInicial: {
    type: String,
    default: 'asc',
    validator: (v) => ['asc', 'desc'].includes(v),
  },

  /** Classes extras do <th> (responsividade, alinhamento, largura). */
  classe: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['ordenar']);

const BASE = 'px-4 py-3 font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider text-xs whitespace-nowrap';

const thClasses = computed(() => [BASE, props.classe]);

const ativa = computed(() => props.coluna !== '' && props.ordenadoPor === props.coluna);

const ariaSort = computed(() => {
  if (!ativa.value) return 'none';
  return props.direcao === 'asc' ? 'ascending' : 'descending';
});

const titulo = computed(() => {
  if (!ativa.value) return 'Ordenar';
  return props.direcao === 'asc' ? 'Ordenado crescente' : 'Ordenado decrescente';
});

// Primeiro clique usa a direcao inicial da coluna; cliques seguintes alternam.
function alternar() {
  const proxima = ativa.value
    ? (props.direcao === 'asc' ? 'desc' : 'asc')
    : props.direcaoInicial;

  emit('ordenar', { sort: props.coluna, direction: proxima });
}
</script>
