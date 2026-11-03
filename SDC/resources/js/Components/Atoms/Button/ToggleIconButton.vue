<template>
  <button
    type="button"
    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border-2 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-1 dark:focus-visible:ring-offset-slate-900"
    :class="ativo
      ? 'border-blue-800 bg-blue-600 text-white hover:bg-blue-500 dark:border-blue-300'
      : 'border-blue-200 bg-white text-blue-600 hover:border-blue-400 hover:bg-blue-50 dark:border-blue-500/40 dark:bg-slate-800/60 dark:text-blue-400 dark:hover:bg-slate-800'"
    :title="title"
    :aria-label="title"
    :aria-pressed="ativo"
    @click="$emit('click')"
  >
    <component :is="icon" class="h-5 w-5" />
  </button>
</template>

<script setup>
/**
 * Botao so de icone que liga/desliga algo (painel, filtro). Ativo = azul cheio
 * com borda escura; inativo = contorno. O `title` e obrigatorio: icone sem
 * rotulo precisa dele para toque longo e leitor de tela.
 */
defineProps({
  icon: { type: [Object, Function], required: true },
  ativo: { type: Boolean, default: false },
  title: { type: String, required: true },
});

defineEmits(['click']);
</script>
