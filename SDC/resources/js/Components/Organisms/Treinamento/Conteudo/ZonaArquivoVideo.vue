<script setup>
/**
 * Area de arrastar ou clicar para escolher o arquivo de video. So valida tipo
 * e tamanho no cliente (o servidor valida de novo) e devolve o arquivo; quem
 * usa decide o que fazer com ele.
 */
import { ref } from 'vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import { ACCEPT_VIDEO, validarArquivoDeVideo } from '@/Composables/treinamento/useUploadEmPartes';

defineProps({
  desabilitada: { type: Boolean, default: false },
});

const emit = defineEmits(['arquivo', 'erro']);

const arrastando = ref(false);

function entregar(arquivo) {
  if (!arquivo) return;
  const erro = validarArquivoDeVideo(arquivo);
  if (erro) emit('erro', erro);
  else emit('arquivo', arquivo);
}

function escolher(evento) {
  const arquivo = evento.target.files?.[0];
  evento.target.value = '';
  entregar(arquivo);
}

function soltar(evento) {
  arrastando.value = false;
  entregar(evento.dataTransfer?.files?.[0]);
}
</script>

<template>
  <label
    class="flex flex-col items-center justify-center rounded-md border-2 border-dashed px-4 py-5 text-center text-sm"
    :class="[
      arrastando ? 'border-sky-500 bg-sky-50 dark:bg-sky-900/20' : 'border-slate-300 dark:border-slate-600',
      desabilitada ? 'cursor-not-allowed opacity-60' : 'cursor-pointer',
    ]"
    @dragover.prevent="arrastando = !desabilitada"
    @dragleave="arrastando = false"
    @drop.prevent="!desabilitada && soltar($event)"
  >
    <input type="file" class="sr-only" :accept="ACCEPT_VIDEO" :disabled="desabilitada" @change="escolher">
    <span class="font-medium">Arraste o vídeo aqui ou clique para escolher</span>
    <Text size="xs" color="muted">MP4, MOV ou MKV, até 2 GB.</Text>
  </label>
</template>
