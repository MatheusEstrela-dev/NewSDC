<script setup>
/**
 * Um bloco de contatos concatenados, pronto para colar no campo de destinatarios do
 * Outlook.
 *
 * MOLECULA: nao chama API, nao usa estado global e nao conhece a area de
 * transferencia. O clique so emite `copiar`; quem copia e o organismo
 * ContatoBlocosOutlook, onde useCopiarTexto e invocado uma unica vez.
 *
 * A prop `copiado` e o feedback devolvido pelo organismo para ESTE bloco -- o
 * organismo guarda qual indice foi copiado por ultimo. E a razao de existirem duas
 * props que pareceriam desnecessarias: sem elas, a molecula teria de guardar estado.
 */
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';

defineProps({
  indice: { type: Number, required: true },
  total: { type: Number, required: true },
  texto: { type: String, required: true },
  copiado: { type: Boolean, default: false },
});

defineEmits(['copiar']);
</script>

<template>
  <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
    <!-- min-w-0 no pai e shrink-0 no botao: as duas metades da regra de nao transbordar -->
    <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
      <div class="min-w-0">
        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
          Parte {{ indice }}
        </p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ total }} contatos neste bloco
        </p>
      </div>

      <button
        type="button"
        class="inline-flex h-10 shrink-0 items-center gap-2 rounded-lg border px-3 text-sm font-semibold transition-colors"
        :class="copiado
          ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300'
          : 'border-slate-200 text-slate-700 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800'"
        :title="`Copiar os ${total} contatos da parte ${indice}`"
        @click="$emit('copiar')"
      >
        <component :is="copiado ? CheckIcon : ClipboardIcon" class="h-4 w-4 shrink-0" />
        <span>{{ copiado ? 'Copiado' : 'Copiar' }}</span>
      </button>
    </div>

    <!--
      O texto de 50 e-mails passa de 1500 caracteres sem quebra natural. break-words
      quebra nos separadores; [overflow-wrap:anywhere] cobre o caso de UM e-mail mais
      largo que a viewport de 375px, que nao tem onde quebrar. A altura e limitada e a
      rolagem e VERTICAL, dentro do bloco: rolagem horizontal esconderia metade dos
      destinatarios atras de um gesto que o usuario nao tem motivo para tentar.
    -->
    <p
      class="mt-3 max-h-48 overflow-y-auto rounded-lg bg-slate-50 p-3 text-xs leading-relaxed text-slate-700 break-words [overflow-wrap:anywhere] dark:bg-slate-950/60 dark:text-slate-300"
    >
      {{ texto }}
    </p>
  </div>
</template>
