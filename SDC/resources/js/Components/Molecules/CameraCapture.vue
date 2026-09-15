<template>
  <div class="rounded-xl border border-slate-200 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-800/40 p-3">
    <div v-if="erro" class="mb-3 text-sm text-red-600 dark:text-red-400">{{ erro }}</div>

    <div v-show="ativo" class="relative overflow-hidden rounded-lg bg-black">
      <video ref="videoRef" class="w-full max-h-80 object-contain" playsinline muted autoplay></video>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-2 mt-3">
      <template v-if="ativo">
        <Button variant="primary" size="md" type="button" :icon="CameraIcon" icon-position="left" :disabled="capturando" @click="capturar">
          {{ capturando ? 'Capturando...' : 'Capturar' }}
        </Button>
        <Button v-if="temMaisDeUmaCamera" variant="outline" size="md" type="button" @click="alternarCamera">
          Virar câmera
        </Button>
        <Button variant="outline" size="md" type="button" @click="parar">Fechar</Button>
      </template>

      <Button v-else variant="primary" size="md" type="button" :icon="CameraIcon" icon-position="left" @click="iniciar">
        Abrir câmera
      </Button>
    </div>

    <!-- Sem getUserMedia (navegador antigo, ou pagina servida em http fora de
         localhost) a camera ao vivo nao existe. O input com `capture` chama o
         app de camera do proprio aparelho e resolve o mesmo problema. -->
    <input
      v-if="!suportaCameraAoVivo"
      ref="inputNativo"
      type="file"
      class="hidden"
      accept="image/*"
      capture="environment"
      @change="aoEscolherDoAparelho"
    />
  </div>
</template>

<script setup>
/**
 * Camera ao vivo dentro da pagina, com captura para arquivo.
 *
 * Mesmo desenho do QrScanner que Plantao e Treinamento ja usam -- <video>
 * alimentado por getUserMedia, stream encerrada ao desmontar -- mas em vez de
 * decodificar um codigo, congela o quadro num canvas e emite um File pronto
 * para upload.
 *
 * Vive na raiz de Molecules/, ao lado do QrScanner, porque nao sabe nada do
 * modulo que o consome: quem decide o que fazer com o arquivo e o pai.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CameraIcon from '@/Components/Icons/CameraIcon.vue';

const props = defineProps({
  /** Prefixo do nome do arquivo gerado. */
  prefixoDoNome: { type: String, default: 'foto' },
  /** Qualidade do JPEG (0..1). 0.85 mantem legibilidade de placa e lacre. */
  qualidade: { type: Number, default: 0.85 },
});

const emit = defineEmits(['captura']);

const videoRef = ref(null);
const inputNativo = ref(null);
const ativo = ref(false);
const capturando = ref(false);
const erro = ref('');
const temMaisDeUmaCamera = ref(false);
const cameraTraseira = ref(true);

let stream = null;

const suportaCameraAoVivo = typeof navigator !== 'undefined'
  && Boolean(navigator.mediaDevices?.getUserMedia);

async function detectarCameras() {
  try {
    const dispositivos = await navigator.mediaDevices.enumerateDevices();
    temMaisDeUmaCamera.value = dispositivos.filter((d) => d.kind === 'videoinput').length > 1;
  } catch {
    temMaisDeUmaCamera.value = false;
  }
}

async function iniciar() {
  erro.value = '';

  if (!suportaCameraAoVivo) {
    inputNativo.value?.click();

    return;
  }

  try {
    stream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: cameraTraseira.value ? 'environment' : 'user' },
      audio: false,
    });

    videoRef.value.srcObject = stream;
    await videoRef.value.play();
    ativo.value = true;

    detectarCameras();
  } catch {
    // Permissao negada, camera em uso por outro app, ou origem insegura.
    // Cair no app de camera do aparelho e melhor que deixar a pessoa sem saida.
    erro.value = 'Nao foi possivel abrir a camera aqui. Tente pelo aplicativo de camera do aparelho.';
    inputNativo.value?.click();
  }
}

function parar() {
  stream?.getTracks().forEach((track) => track.stop());
  stream = null;

  if (videoRef.value) {
    videoRef.value.srcObject = null;
  }

  ativo.value = false;
}

async function alternarCamera() {
  cameraTraseira.value = !cameraTraseira.value;
  parar();
  await iniciar();
}

function capturar() {
  const video = videoRef.value;

  if (!video || !video.videoWidth) {
    return;
  }

  capturando.value = true;

  const canvas = document.createElement('canvas');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

  canvas.toBlob(
    (blob) => {
      capturando.value = false;

      if (!blob) {
        erro.value = 'Nao foi possivel gerar a imagem.';

        return;
      }

      const nome = `${props.prefixoDoNome}-${Date.now()}.jpg`;
      emit('captura', new File([blob], nome, { type: 'image/jpeg' }));
    },
    'image/jpeg',
    props.qualidade,
  );
}

function aoEscolherDoAparelho(evento) {
  const arquivo = evento.target.files?.[0];

  if (arquivo) {
    emit('captura', arquivo);
  }

  // Zera o input: sem isso, fotografar o mesmo enquadramento duas vezes
  // seguidas nao dispara `change` na segunda.
  evento.target.value = '';
}

onMounted(() => {
  if (suportaCameraAoVivo) {
    detectarCameras();
  }
});

// A stream continua viva depois que o componente sai de tela se ninguem parar
// as tracks -- no celular isso deixa a luz da camera acesa e drena bateria.
onBeforeUnmount(parar);

defineExpose({ iniciar, parar });
</script>
