<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { enviarProgressoKeepalive } from '@/Composables/treinamento/enviarProgressoKeepalive';
import { usePulsoSessao } from '@/Composables/treinamento/usePulsoSessao';

const INTERVALO_POSICAO_MS = 15000;
const MAX_RECUPERACOES_MIDIA = 2;
const BUFFER_ATRAS_SEGUNDOS = 90;
// Banda presumida antes da primeira medicao. O padrao do hls.js (500 kbps) fica
// abaixo do 720p e abria toda aula no 480p; com 5 Mbps ela abre na maior
// qualidade que cabe no player, e o ABR so desce se a rede nao sustentar.
const BANDA_INICIAL_BPS = 5_000_000;
const TIPO_HLS_NATIVO = 'application/vnd.apple.mpegurl';
const MENSAGEM_ERRO = 'Não foi possível reproduzir este vídeo. Tente novamente em instantes.';
const MENSAGEM_SEM_SUPORTE = 'Este navegador não consegue reproduzir este vídeo.';
const MENSAGEM_SESSAO = 'Sua sessão expirou. Recarregue a página ou entre novamente para continuar.';
const MENSAGEM_ACESSO = 'Você não tem acesso a este vídeo.';

const props = defineProps({
  itemId: { type: [Number, String], required: true },
  urlMaster: { type: String, required: true },
  posicaoInicial: { type: Number, default: 0 },
  concluido: { type: Boolean, default: false },
  urlProgresso: { type: String, default: '' },
  urlPulso: { type: String, default: '' },
});

const emit = defineEmits(['posicao', 'terminou']);

const video = ref(null);
const carregando = ref(true);
const erro = ref('');

const pulso = usePulsoSessao(() => props.urlPulso);

let hls = null;
let intervalo = null;
let desmontado = false;
let geracao = 0;
let tentouRede = false;
let recuperacoes = 0;
let nativo = false;
let maxAlcancado = 0;
let ultimoKeepalive = 0;

// Estado de tempo isolado: nada reativo por segundo. Guarda so o maior segundo
// inteiro alcancado e so escreve quando ele muda.
function aoAtualizarTempo() {
  const atual = Math.floor(video.value?.currentTime ?? 0);
  if (atual > maxAlcancado) maxAlcancado = atual;
}

function emitirPosicao() {
  if (maxAlcancado > 0) emit('posicao', maxAlcancado, props.itemId);
}

function pararIntervalo() {
  clearInterval(intervalo);
  intervalo = null;
}

function aoTocar() {
  pararIntervalo();
  intervalo = setInterval(emitirPosicao, INTERVALO_POSICAO_MS);
  pulso.iniciar();
}

function aoPausar() {
  pararIntervalo();
  pulso.parar();
  emitirPosicao();
}

function aoTerminar() {
  pararIntervalo();
  pulso.parar();
  const duracao = video.value ? Math.floor(video.value.duration) : 0;
  emit('terminou', Number.isFinite(duracao) ? duracao : 0, props.itemId);
}

// Ao esconder a aba ou sair da pagina o POST precisa sobreviver ao descarregamento:
// com a rota de progresso conhecida vai por fetch keepalive (so se avancou alem do
// salvo); sem ela, cai no emit normal.
function salvarAoSair() {
  const avancou = maxAlcancado > Math.max(ultimoKeepalive, Math.floor(props.posicaoInicial));
  if (props.urlProgresso && !props.concluido) {
    if (avancou && enviarProgressoKeepalive(props.urlProgresso, maxAlcancado)) ultimoKeepalive = maxAlcancado;

    return;
  }
  emitirPosicao();
}

function aoMudarVisibilidade() {
  if (document.visibilityState === 'hidden') salvarAoSair();
}

function aoCarregarMetadados() {
  carregando.value = false;
  // O hls.js ja parte da posicao salva (startPosition); o seek so vale no HLS nativo.
  if (nativo && !props.concluido && props.posicaoInicial > 0) video.value.currentTime = props.posicaoInicial;
}

function mostrarErro(mensagem = MENSAGEM_ERRO) {
  erro.value = mensagem;
  carregando.value = false;
}

// Com hls.js as falhas de midia chegam pelo evento ERROR dele. A excecao e a
// fonte recusada pelo navegador antes de abrir (ex.: bloqueio de CSP): o
// hls.js nao fica sabendo e o player ficaria carregando para sempre.
function fonteRecusada() {
  return carregando.value && video.value?.error?.code === MediaError.MEDIA_ERR_SRC_NOT_SUPPORTED;
}

function aoFalharElemento() {
  if (!nativo && !fonteRecusada()) return;

  liberar();
  mostrarErro();
}

function mensagemDeResposta(dados) {
  const status = dados.response?.code;
  if (status === 401) return MENSAGEM_SESSAO;
  if (status === 403) return MENSAGEM_ACESSO;

  return MENSAGEM_ERRO;
}

// Recuperou: volta a ter direito as tentativas, para uma queda de rede no meio
// de uma aula longa nao consumir a unica chance para sempre.
function aoRecuperar() {
  tentouRede = false;
  recuperacoes = 0;
}

function aoFalhar(_evento, dados) {
  if (!dados.fatal) return;

  const mensagem = mensagemDeResposta(dados);
  if (mensagem !== MENSAGEM_ERRO) {
    liberar();
    mostrarErro(mensagem);
  } else if (dados.type === 'networkError' && !tentouRede) {
    tentouRede = true;
    hls.startLoad();
  } else if (dados.type === 'mediaError' && recuperacoes < MAX_RECUPERACOES_MIDIA) {
    recuperacoes++;
    hls.recoverMediaError();
  } else {
    liberar();
    mostrarErro();
  }
}

function liberarHls() {
  if (!hls) return;

  hls.stopLoad();
  hls.detachMedia();
  hls.destroy();
  hls = null;
}

// Caminho nativo (Safari): remover o src e recarregar solta a conexao.
function liberarNativo() {
  if (!nativo || !video.value) return;

  video.value.removeAttribute('src');
  video.value.load();
}

function liberar() {
  geracao++;
  pararIntervalo();
  pulso.parar();
  liberarHls();
  liberarNativo();
  nativo = false;
}

function configuracaoHls() {
  const config = {
    capLevelToPlayerSize: true,
    abrEwmaDefaultEstimate: BANDA_INICIAL_BPS,
    backBufferLength: BUFFER_ATRAS_SEGUNDOS,
  };
  if (!props.concluido && props.posicaoInicial > 0) config.startPosition = Math.floor(props.posicaoInicial);

  return config;
}

async function carregar() {
  const minha = ++geracao;
  erro.value = '';
  carregando.value = true;
  tentouRede = false;
  recuperacoes = 0;
  nativo = false;
  maxAlcancado = Math.max(0, Math.floor(props.posicaoInicial));
  ultimoKeepalive = 0;

  try {
    const { default: Hls } = await import('hls.js');
    if (desmontado || minha !== geracao || !video.value) return;

    if (Hls.isSupported()) {
      hls = new Hls(configuracaoHls());
      hls.on(Hls.Events.ERROR, aoFalhar);
      hls.on(Hls.Events.FRAG_BUFFERED, aoRecuperar);
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
  window.addEventListener('pagehide', salvarAoSair);
  carregar();
});

watch(() => props.urlMaster, () => {
  emitirPosicao();
  liberar();
  carregar();
});

onBeforeUnmount(() => {
  desmontado = true;
  emitirPosicao();
  document.removeEventListener('visibilitychange', aoMudarVisibilidade);
  window.removeEventListener('pagehide', salvarAoSair);
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
