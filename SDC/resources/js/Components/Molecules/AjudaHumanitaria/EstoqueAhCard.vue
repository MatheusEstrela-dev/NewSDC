<template>
  <div
    class="bg-white dark:bg-slate-900/60 rounded-lg border p-4 sm:p-5 transition-colors"
    :class="borda"
  >
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
          {{ saldo.sigla }} · {{ saldo.deposito }}
        </p>
        <h4 class="mt-1 font-medium text-slate-900 dark:text-white break-words">
          {{ saldo.material }}
        </h4>
      </div>

      <span
        class="shrink-0 px-2 py-0.5 rounded-full text-xs font-medium ring-1"
        :class="etiqueta"
      >
        {{ rotuloNivel }}
      </span>
    </div>

    <div class="mt-4 flex items-baseline gap-2">
      <span class="text-2xl font-semibold text-slate-900 dark:text-white tabular-nums">
        {{ numero.format(saldo.saldo) }}
      </span>
      <span class="text-sm text-slate-500 dark:text-slate-400">{{ saldo.unidade }}</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  saldo: {
    type: Object,
    required: true,
  },
});

const numero = new Intl.NumberFormat('pt-BR');

/**
 * Mesmas faixas dos cartoes de filtro rapido, definidas no EstoqueAhController.
 * Duplicar o limite aqui e proposital e conhecido: o card precisa da cor sem
 * mais uma ida ao servidor. Se a CEDEC parametrizar o ponto de reposicao, o
 * nivel passa a vir no payload e este bloco sai.
 */
const nivel = computed(() => {
  if (props.saldo.saldo < 50) return 'critico';

  return props.saldo.saldo < 200 ? 'baixo' : 'confortavel';
});

const rotuloNivel = computed(() => ({
  critico: 'Crítico',
  baixo: 'Baixo',
  confortavel: 'Confortável',
}[nivel.value]));

const borda = computed(() => ({
  critico: 'border-red-200 dark:border-red-500/25',
  baixo: 'border-amber-200 dark:border-amber-500/25',
  confortavel: 'border-slate-200 dark:border-slate-700/30',
}[nivel.value]));

const etiqueta = computed(() => ({
  critico: 'bg-red-100 dark:bg-red-500/15 text-red-700 dark:text-red-300 ring-red-300 dark:ring-red-500/25',
  baixo: 'bg-amber-100 dark:bg-amber-500/15 text-amber-700 dark:text-amber-300 ring-amber-300 dark:ring-amber-500/25',
  confortavel: 'bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 ring-emerald-300 dark:ring-emerald-500/25',
}[nivel.value]));
</script>
