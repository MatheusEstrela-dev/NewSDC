<template>
  <button
    type="button"
    :disabled="disabled"
    :title="title"
    :aria-label="title"
    role="checkbox"
    :aria-checked="state === 'all' ? 'true' : (state === 'some' ? 'mixed' : 'false')"
    class="w-6 h-6 sm:w-5 sm:h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition-colors"
    :class="[
      disabled
        ? 'border-slate-200 dark:border-slate-700 opacity-40 cursor-not-allowed'
        : 'cursor-pointer',
      !disabled && state === 'all' ? 'bg-purple-600 border-purple-600' : '',
      !disabled && state === 'some' ? 'bg-purple-100 dark:bg-purple-900/40 border-purple-500' : '',
      !disabled && state === 'none' ? 'border-slate-300 dark:border-slate-600 hover:border-purple-400' : ''
    ]"
    @click.stop="$emit('toggle')"
  >
    <svg v-if="state === 'all'" class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
    </svg>
    <span v-else-if="state === 'some'" class="w-2.5 h-0.5 rounded-full bg-purple-600 dark:bg-purple-400"></span>
  </button>
</template>

<script setup>
/**
 * Checkbox de tres estados para marcar/desmarcar um conjunto inteiro de itens.
 *
 * Nao e um <input type="checkbox"> porque o estado "some" (indeterminate) so
 * existe como propriedade DOM: no Vue exigiria um ref por instancia so para
 * escrever `el.indeterminate`. Como <button role="checkbox"> ele carrega o
 * aria-checked="mixed", que e a semantica correta e chega pronta ao leitor de
 * tela.
 *
 * O @click.stop e essencial: este controle vive dentro de cabecalhos clicaveis
 * (accordion de modulo), e sem isso marcar tudo tambem fecharia a secao.
 */
defineProps({
  /** 'all' | 'some' | 'none' */
  state: {
    type: String,
    default: 'none',
    validator: (v) => ['all', 'some', 'none'].includes(v),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Marcar tudo',
  },
});

defineEmits(['toggle']);
</script>
