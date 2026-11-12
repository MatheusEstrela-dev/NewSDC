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
  <span v-if="codigo" class="inline-flex items-center gap-1.5">
    <span class="font-mono font-medium">{{ codigo }}</span>
    <button
      type="button"
      class="rounded p-0.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:text-slate-400 dark:hover:bg-slate-700 dark:hover:text-slate-200"
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
      class="rounded p-0.5 text-blue-600 hover:bg-slate-100 dark:text-blue-400 dark:hover:bg-slate-700"
      title="Abrir a página de validação"
      aria-label="Abrir a página pública de validação"
    >
      <ArrowTopRightOnSquareIcon class="h-4 w-4" />
    </a>
  </span>
</template>
