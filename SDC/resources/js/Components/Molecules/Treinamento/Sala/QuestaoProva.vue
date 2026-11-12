<script setup>
import { useId } from 'vue';

defineProps({
  questao: { type: Object, required: true },
  numero: { type: Number, required: true },
  total: { type: Number, required: true },
  selecionada: { type: Number, default: null },
  desabilitada: { type: Boolean, default: false },
});

const emit = defineEmits(['responder']);

const nome = `questao-${useId()}`;
</script>

<template>
  <fieldset class="space-y-3" :disabled="desabilitada">
    <legend class="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
      Questão {{ numero }} de {{ total }}
    </legend>
    <p class="whitespace-pre-line text-base font-medium text-slate-800 dark:text-slate-100">{{ questao.enunciado }}</p>

    <div class="space-y-2" role="radiogroup">
      <label
        v-for="alternativa in questao.alternativas"
        :key="alternativa.id"
        class="flex cursor-pointer items-start gap-3 rounded-lg border p-3 text-sm transition-colors focus-within:ring-2 focus-within:ring-amber-500"
        :class="selecionada === alternativa.id
          ? 'border-amber-500 bg-amber-50 text-slate-900 dark:border-amber-400 dark:bg-amber-500/10 dark:text-slate-100'
          : 'border-slate-200 text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700/40'"
      >
        <input
          type="radio"
          class="mt-0.5 h-4 w-4 flex-none accent-amber-500"
          :name="nome"
          :value="alternativa.id"
          :checked="selecionada === alternativa.id"
          @change="emit('responder', alternativa.id)"
        >
        <span class="min-w-0 flex-1 whitespace-pre-line">{{ alternativa.texto }}</span>
      </label>
    </div>
  </fieldset>
</template>
