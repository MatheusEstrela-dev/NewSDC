<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { pendenciasDe } from '@/Composables/treinamento/useProvaApi';

/**
 * Motivos que o backend devolve quando bloqueia a publicacao do curso
 * (pasta sem prova no fim, prova sem questao, questao sem correta). Recebe o
 * `errors` do Inertia; `errors.publicar` e o texto de apoio quando nao ha lista.
 */
const props = defineProps({
  erros: { type: Object, default: () => ({}) },
  hrefProvas: { type: String, default: '' },
});

const itens = computed(() => pendenciasDe(props.erros));
const apoio = computed(() => (itens.value.length === 0 ? props.erros?.publicar ?? '' : ''));
</script>

<template>
  <div
    v-if="itens.length || apoio"
    role="alert"
    class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200"
  >
    <p class="font-semibold">O curso nao pode ser publicado ainda.</p>
    <p v-if="apoio" class="mt-2">{{ apoio }}</p>
    <ul v-if="itens.length" class="mt-2 list-disc space-y-1 pl-5">
      <li v-for="(item, indice) in itens" :key="indice">{{ item }}</li>
    </ul>
    <Link v-if="hrefProvas" :href="hrefProvas" class="mt-3 inline-block font-medium underline">Ir para as provas</Link>
  </div>
</template>
