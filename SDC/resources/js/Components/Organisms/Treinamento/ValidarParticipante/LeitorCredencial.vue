<script setup>
import { ref } from 'vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import QrScanner from '@/Components/Molecules/QrScanner.vue';
import { extrairTokenCredencial } from '@/Composables/treinamento/extrairTokenCredencial';

/*
 * Leitura da credencial pela camera (QrScanner compartilhado). Emite `ler` com
 * o token ja extraido; quem consulta e a pagina. A pesquisa manual (CPF e
 * certificado) vive em PesquisaServidor.
 */
const emit = defineEmits(['ler']);

const aviso = ref('');
// O leitor dispara varias vezes enquanto o QR esta na frente da camera e o
// componente segue montado entre consultas: o mesmo codigo so vale de novo apos a pausa.
const PAUSA_MS = 8000;
let ultimo = { token: null, em: 0 };

function entregar(texto) {
  const token = extrairTokenCredencial(texto);

  if (!token) {
    aviso.value = 'Este QR não é uma credencial de servidor.';
    return;
  }

  const agora = Date.now();

  if (ultimo.token === token && agora - ultimo.em < PAUSA_MS) return;

  ultimo = { token, em: agora };
  aviso.value = '';
  navigator.vibrate?.(60);
  emit('ler', token);
}
</script>

<template>
  <div class="space-y-4">
    <CardBase padding="sm" class="leitor-camera">
      <QrScanner @decode="entregar" />
      <p class="px-2 pb-2 text-center text-sm text-slate-600 dark:text-slate-300">
        Aponte a câmera para o QR da credencial do servidor.
      </p>
    </CardBase>

    <p
      v-if="aviso"
      class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
      role="alert"
    >
      {{ aviso }}
    </p>
  </div>
</template>

<style scoped>
/* Area de leitura maior no celular: o QrScanner limita o video a 18rem. */
.leitor-camera :deep(video) {
  max-height: 60vh;
  min-height: 16rem;
}
.leitor-camera :deep(button) {
  min-height: 44px;
}
</style>
