<script setup>
/**
 * Camera + leitura de QR Code. Compartilhado entre modulos: emite o texto
 * decodificado e nao sabe nada sobre o que ele significa -- Treinamento le
 * ingresso de inscricao, Plantao le etiqueta de chaveiro.
 *
 * Vive na raiz de Molecules/ (com FlashNotification e PullToRefresh) e nao numa
 * pasta de modulo justamente porque o segundo consumidor apareceu.
 */
import { onBeforeUnmount, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';

const emit = defineEmits(['decode']);

const videoRef = ref(null);
const ativo = ref(false);
const erro = ref('');
let scanner = null;

async function iniciar() {
  erro.value = '';

  try {
    const { default: QrScanner } = await import('qr-scanner');

    scanner = new QrScanner(
      videoRef.value,
      (resultado) => emit('decode', resultado.data ?? resultado),
      { highlightScanRegion: true, highlightCodeOutline: true }
    );

    await scanner.start();
    ativo.value = true;
  } catch (e) {
    erro.value = 'Nao foi possivel acessar a camera. Verifique as permissoes do navegador.';
  }
}

function parar() {
  scanner?.stop();
  scanner?.destroy();
  scanner = null;
  ativo.value = false;
}

onBeforeUnmount(() => parar());

defineExpose({ parar });
</script>

<template>
  <div class="qr-scanner">
    <div v-if="!ativo" class="flex flex-col items-center gap-3 py-6">
      <Button variant="primary" size="sm" @click="iniciar">Ativar câmera / ler QR Code</Button>
      <p v-if="erro" class="text-xs text-red-500">{{ erro }}</p>
    </div>
    <div v-show="ativo" class="relative overflow-hidden rounded-lg border border-slate-200 dark:border-slate-700">
      <video ref="videoRef" class="w-full max-h-72 object-cover"></video>
      <button
        type="button"
        class="absolute top-2 right-2 rounded-md bg-black/60 px-2 py-1 text-xs text-white hover:bg-black/80"
        @click="parar"
      >
        Parar
      </button>
    </div>
  </div>
</template>
