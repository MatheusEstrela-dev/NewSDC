<template>
  <fieldset class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <legend class="px-2 text-sm font-bold text-slate-900 dark:text-slate-100">{{ titulo }}</legend>

    <p v-if="descricao" class="mb-3 text-xs text-slate-500 dark:text-slate-400">{{ descricao }}</p>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
      <slot />
    </div>
  </fieldset>
</template>

<script setup>
/**
 * Bloco de campos do formulario de beneficiario.
 *
 * O cadastro tem 45 campos, e sem agrupamento visual o usuario perde o fio --
 * no legado eram 556 linhas de Blade numa coluna unica. Cada secao e um
 * fieldset de verdade, o que tambem da navegacao por teclado e leitura correta
 * por leitor de tela.
 */
defineProps({
  titulo: { type: String, required: true },
  descricao: { type: String, default: '' },
});
</script>
