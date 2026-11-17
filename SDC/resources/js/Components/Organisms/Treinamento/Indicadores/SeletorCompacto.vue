<template>
  <label class="flex min-w-0 flex-col gap-1">
    <span class="text-xs font-medium" :class="[ocultarRotulo ? 'sr-only' : '', sobreDestaque ? 'text-orange-100 dark:text-orange-300/90' : 'text-slate-500 dark:text-slate-400']">{{ rotulo }}</span>
    <select
      :value="modelValue"
      class="h-8 w-full min-w-0 max-w-full truncate rounded-lg border px-2 text-xs focus:outline-none focus:ring-2"
      :class="sobreDestaque
        ? 'border-transparent bg-white text-slate-900 focus:ring-orange-200 dark:border-orange-500/30 dark:bg-slate-900/70 dark:text-slate-100 dark:focus:ring-orange-500/40'
        : 'border-slate-300 bg-white text-slate-700 focus:ring-blue-500/40 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-for="opcao in opcoes" :key="opcao.value" :value="opcao.value">{{ opcao.label }}</option>
    </select>
  </label>
</template>

<script setup>
/**
 * Dropdown pequeno do painel (cabecalho de painel, card de destaque, filtros da
 * tabela). O valor sobe como string, que e o que o select devolve; a Index
 * decide o que fazer com ele. `opcoes` ja traz a opcao "todos" (value '').
 */
defineProps({
  modelValue: { type: String, default: '' },
  rotulo: { type: String, required: true },
  opcoes: { type: Array, default: () => [] },
  /** Mantem o rotulo so para leitor de tela (cabecalho de painel, sem espaco). */
  ocultarRotulo: { type: Boolean, default: false },
  /** Versao para fundo verde: campo branco, rotulo claro. */
  sobreDestaque: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>
