<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const INTERVALO_POSICAO_MS = 15000;
const MAX_RECUPERACOES_MIDIA = 2;
const TIPO_HLS_NATIVO = 'application/vnd.apple.mpegurl';
const MENSAGEM_ERRO = 'Não foi possível reproduzir este vídeo. Tente novamente em instantes.';
const MENSAGEM_SEM_SUPORTE = 'Este navegador não consegue reproduzir este vídeo.';

const props = defineProps({
  itemId: { type: [Number, String], required: true },
  urlMaster: { type: String, required: true },
  posicaoInicial: { type: Number, default: 0 },
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
let recuperacoes = 0;
let nativo = false;
let maxAlcancado = 0;

function emitirPosicao() {
  if (maxAlcancado > 0) emit('posicao', maxAlcancado, props.itemId);
}

function aoAtualizarTempo() {
  const atual = Math.floor(video.value?.currentTime ?? 0);
  if (atual > maxAlcancado) maxAlcancado = atual;
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
  const duracao = video.value ? Math.floor(video.value.duration) : 0;
  emit('terminou', Number.isFinite(duracao) ? duracao : 0, props.itemId);
}

function aoMudarVisibilidade() {
  if (document.visibilityState === 'hidden') emitirPosicao();
}

function aoCarregarMetadados() {
  carregando.value = false;
  if (!props.concluido && props.posicaoInicial > 0) video.value.currentTime = props.posicaoInicial;
}

function mostrarErro(mensagem = MENSAGEM_ERRO) {
  erro.value = mensagem;
  carregando.value = false;
}

function aoFalharElemento() {
  if (nativo) mostrarErro();
}

function aoFalhar(_evento, dados) {
  if (!dados.fatal) return;

  if (dados.type === 'networkError' && !tentouRede) {
    tentouRede = true;
    hls.startLoad();
  } else if (dados.type === 'mediaError' && recuperacoes < MAX_RECUPERACOES_MIDIA) {
    recuperacoes++;
    hls.recoverMediaError();
  } else {
    hls?.destroy();
    hls = null;
    mostrarErro();
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
  recuperacoes = 0;
  nativo = false;
  maxAlcancado = Math.max(0, Math.floor(props.posicaoInicial));

  try {
    const { default: Hls } = await import('hls.js');
    if (desmontado || minha !== geracao || !video.value) return;

    if (Hls.isSupported()) {
      hls = new Hls({ enableWorker: true });
      hls.on(Hls.Events.ERROR, aoFalhar);
      hls.loadSource(props.urlMaster);
      hls.attachMedia(video.value);
    } else if (video.value.canPlayType(TIPO_HLS_NATIVO)) {
      nativo = true;
      video.value.src = props.urlMaster;
    } else {
      mostrarErro(MENSAGEM_SEM_SUPORTE);
    }
  } catch {
    if (desmontado || minha !== geracao) return;
    mostrarErro();
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
      @timeupdate="aoAtualizarTempo"
      @error="aoFalharElemento"
      @ended="aoTerminar"
    />
    <p v-if="carregando && !erro" class="absolute text-sm text-white">Carregando o vídeo...</p>
    <p v-if="erro" role="alert" class="p-6 text-center text-sm text-white">{{ erro }}</p>
  </div>
</template>
