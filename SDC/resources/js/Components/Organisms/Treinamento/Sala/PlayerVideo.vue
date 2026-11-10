<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const INTERVALO_POSICAO_MS = 15000;
const MENSAGEM_ERRO = 'Nao foi possivel reproduzir este video. Tente novamente em instantes.';

const props = defineProps({
  urlMaster: { type: String, required: true },
  posicaoInicial: { type: Number, default: 0 },
  duracao: { type: Number, default: 0 },
  concluido: { type: Boolean, default: false },
});

const emit = defineEmits(['posicao', 'terminou']);

const video = ref(null);
const carregando = ref(true);
const erro = ref('');

let hls = null;
let intervalo = null;
let desmontado = false;
let geracao = 0;
let tentouRede = false;

function emitirPosicao() {
  if (video.value) emit('posicao', Math.floor(video.value.currentTime));
}

function pararIntervalo() {
  clearInterval(intervalo);
  intervalo = null;
}

function aoTocar() {
  pararIntervalo();
  intervalo = setInterval(emitirPosicao, INTERVALO_POSICAO_MS);
}

function aoPausar() {
  pararIntervalo();
  emitirPosicao();
}

function aoTerminar() {
  pararIntervalo();
  emit('terminou');
}

function aoMudarVisibilidade() {
  if (document.visibilityState === 'hidden') emitirPosicao();
}

function aoCarregarMetadados() {
  carregando.value = false;
  if (!props.concluido && props.posicaoInicial > 0) video.value.currentTime = props.posicaoInicial;
}

function aoFalhar(_evento, dados) {
  if (!dados.fatal) return;

  if (dados.type === 'networkError' && !tentouRede) {
    tentouRede = true;
    hls.startLoad();
  } else if (dados.type === 'mediaError') {
    hls.recoverMediaError();
  } else {
    erro.value = MENSAGEM_ERRO;
    carregando.value = false;
  }
}

function liberar() {
  geracao++;
  pararIntervalo();
  hls?.destroy();
  hls = null;
}

async function carregar() {
  const minha = ++geracao;
  erro.value = '';
  carregando.value = true;
  tentouRede = false;

  try {
    const { default: Hls } = await import('hls.js');
    if (desmontado || minha !== geracao || !video.value) return;

    if (Hls.isSupported()) {
      hls = new Hls({ enableWorker: true });
      hls.on(Hls.Events.ERROR, aoFalhar);
      hls.loadSource(props.urlMaster);
      hls.attachMedia(video.value);
    } else {
      video.value.src = props.urlMaster;
    }
  } catch {
    if (desmontado || minha !== geracao) return;
    erro.value = MENSAGEM_ERRO;
    carregando.value = false;
  }
}

onMounted(() => {
  document.addEventListener('visibilitychange', aoMudarVisibilidade);
  carregar();
});

watch(() => props.urlMaster, () => {
  liberar();
  carregar();
});

onBeforeUnmount(() => {
  desmontado = true;
  emitirPosicao();
  document.removeEventListener('visibilitychange', aoMudarVisibilidade);
  liberar();
});
</script>

<template>
  <div class="relative flex min-h-[320px] flex-1 items-center justify-center bg-black">
    <video
      v-show="!erro"
      ref="video"
      controls
      playsinline
      class="max-h-[70vh] w-full"
      @loadedmetadata="aoCarregarMetadados"
      @play="aoTocar"
      @pause="aoPausar"
      @ended="aoTerminar"
    />
    <p v-if="carregando && !erro" class="absolute text-sm text-white">Carregando o video...</p>
    <p v-if="erro" role="alert" class="p-6 text-center text-sm text-white">{{ erro }}</p>
  </div>
</template>
