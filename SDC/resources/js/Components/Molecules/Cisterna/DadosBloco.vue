<template>
  <section
    v-if="visiveis.length > 0"
    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60"
  >
    <h3 class="mb-3 text-sm font-bold text-slate-900 dark:text-slate-100">{{ titulo }}</h3>

    <dl class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="item in visiveis" :key="item.rotulo">
        <dt class="text-xs text-slate-500 dark:text-slate-400">{{ item.rotulo }}</dt>
        <dd class="text-sm text-slate-800 dark:text-slate-100">{{ item.valor }}</dd>
      </div>
    </dl>
  </section>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Bloco de leitura de dados. Molecula burra: recebe pares rotulo/valor ja
 * formatados pela pagina.
 *
 * Campo vazio e OMITIDO, e o bloco inteiro desaparece se nada sobrar. Motivo: os
 * 8.096 cadastros importados tem muito campo nulo (o legado nao exigia quase
 * nada), e uma grade cheia de "—" esconde o que de fato foi preenchido.
 */
const props = defineProps({
  titulo: { type: String, required: true },
  itens: { type: Array, default: () => [] },
});

const visiveis = computed(
  () => (props.itens ?? []).filter(
    (i) => i.valor !== null && i.valor !== undefined && i.valor !== '',
  ),
);
</script>
