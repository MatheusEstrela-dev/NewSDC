<script setup>
/**
 * Aviso de situacao da escala, no molde do PassagemHandshakeBanner.
 *
 * As duas mensagens que a tela precisa dar — "esta em rascunho" e "nao ha
 * escala montada" — eram dois `<div>` com classes soltas no template. Aqui elas
 * viram um componente com um tom por tipo, e o template volta a so orquestrar.
 *
 * Tom em mapa de classes LITERAIS, nunca interpoladas: o Tailwind varre o
 * codigo-fonte em busca de literais, e `border-${tom}-300` nao entraria no CSS
 * final.
 */
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
// Nao ha InformationCircleIcon em Components/Icons; heroicons e a saida que o
// proprio PlantaoIndexTemplate ja usa para icone que falta no conjunto local.
import { InformationCircleIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const TONS = {
  aviso:
    'border-amber-300 bg-amber-50 text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300',
  neutro:
    'border-slate-300 bg-slate-50 text-slate-600 dark:border-slate-700 dark:bg-slate-800/50 dark:text-slate-300',
};

const props = defineProps({
  tom: {
    type: String,
    default: 'neutro',
    validator: (v) => ['aviso', 'neutro'].includes(v),
  },
});

const classes = computed(() => TONS[props.tom] ?? TONS.neutro);

const icone = computed(() =>
  props.tom === 'aviso' ? ExclamationTriangleIcon : InformationCircleIcon,
);
</script>

<template>
  <div
    class="mb-6 flex items-start gap-3 rounded-xl border p-4 text-sm"
    :class="classes"
  >
    <component :is="icone" class="mt-0.5 h-5 w-5 shrink-0" />
    <p class="min-w-0 flex-1">
      <slot />
    </p>
  </div>
</template>
