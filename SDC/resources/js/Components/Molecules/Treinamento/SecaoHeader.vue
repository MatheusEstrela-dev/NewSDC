<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import { ChevronDownIcon } from '@heroicons/vue/24/outline';

/*
 * Cabecalho das secoes da area do aluno e do gestor. Com `expandido` (true ou
 * false) vira o botao de abrir/recolher da secao (ver SecaoRecolhivel); sem ele
 * continua so titulo + contador.
 */
const props = defineProps({
  titulo: { type: String, required: true },
  total: { type: Number, default: null },
  expandido: { type: Boolean, default: null },
});

defineEmits(['alternar']);

const recolhivel = props.expandido !== null;
</script>

<template>
  <component
    :is="recolhivel ? 'button' : 'div'"
    :type="recolhivel ? 'button' : undefined"
    :aria-expanded="recolhivel ? expandido : undefined"
    class="mb-4 flex w-full items-center gap-3 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-left dark:border-slate-700/60 dark:bg-slate-800/60"
    :class="recolhivel ? 'min-h-[44px] cursor-pointer transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:hover:bg-slate-800' : ''"
    @click="recolhivel && $emit('alternar')"
  >
    <span class="h-5 w-1 shrink-0 rounded-full bg-blue-600 dark:bg-blue-400" aria-hidden="true" />
    <Heading :level="2" class="!text-base !font-semibold">{{ titulo }}</Heading>
    <span
      v-if="total !== null"
      class="ml-auto rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-700 dark:text-slate-300"
    >
      {{ total }}
    </span>
    <ChevronDownIcon
      v-if="recolhivel"
      class="h-5 w-5 shrink-0 text-slate-400 transition-transform duration-200"
      :class="[{ 'rotate-180': expandido }, total === null ? 'ml-auto' : '']"
    />
  </component>
</template>
