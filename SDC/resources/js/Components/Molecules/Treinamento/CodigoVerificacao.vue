<script setup>
import { ClipboardDocumentIcon, ArrowTopRightOnSquareIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

/*
 * Codigo de verificacao do certificado, com copiar e link para a validacao
 * publica. Sem codigo (certificado ainda nao emitido), nao renderiza nada.
 */
const props = defineProps({
  codigo: { type: String, default: null },
  urlValidacao: { type: String, default: null },
});

const { show: toast } = useToast();

const classeAcao = 'inline-flex h-7 w-7 items-center justify-center rounded-md border border-slate-300 text-slate-600 transition-colors hover:border-blue-500 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-600 dark:text-slate-300 dark:hover:border-blue-400 dark:hover:bg-slate-700 dark:hover:text-blue-300';

async function copiar() {
  try {
    await navigator.clipboard.writeText(props.codigo);
    toast('Código copiado.', 'success');
  } catch {
    toast('Não foi possível copiar o código.', 'error');
  }
}
</script>

<template>
  <span v-if="codigo" class="inline-flex items-center gap-1.5 align-middle">
    <span class="rounded-md bg-slate-100 px-2 py-1 font-mono text-sm font-semibold tracking-wide text-slate-800 dark:bg-slate-700 dark:text-slate-100">{{ codigo }}</span>
    <button
      type="button"
      :class="classeAcao"
      title="Copiar código"
      aria-label="Copiar código de verificação"
      @click="copiar"
    >
      <ClipboardDocumentIcon class="h-4 w-4" />
    </button>
    <a
      v-if="urlValidacao"
      :href="urlValidacao"
      target="_blank"
      rel="noopener"
      :class="classeAcao"
      title="Abrir a página de validação"
      aria-label="Abrir a página pública de validação"
    >
      <ArrowTopRightOnSquareIcon class="h-4 w-4" />
    </a>
  </span>
</template>
