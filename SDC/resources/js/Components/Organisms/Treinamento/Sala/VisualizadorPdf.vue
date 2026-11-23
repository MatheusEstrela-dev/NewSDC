<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePdf } from '@/Composables/treinamento/usePdf';

const props = defineProps({
  url: { type: String, required: true },
  posicaoInicial: { type: Number, default: 0 },
  totalPaginas: { type: Number, default: 1 },
  concluido: { type: Boolean, default: false },
  // Tempo minimo em cada pagina ainda nao lida; 0 desliga (previa do professor).
  segundosPorPagina: { type: Number, default: 0 },
});

const emit = defineEmits(['pagina']);

const { abrirDocumento, renderizarPagina } = usePdf();

const ANEL_RAIO = 9;
const ANEL_CIRCUNFERENCIA = 2 * Math.PI * ANEL_RAIO;
const TICK_MS = 250;

const ZOOM_MIN = 0.6;
const ZOOM_MAX = 2.4;
const ZOOM_PASSO = 0.2;
const ZOOM_BASE = 1.2;

const canvas = ref(null);
const carregando = ref(true);
const erro = ref(false);
const zoom = ref(ZOOM_BASE);
const total = ref(Math.max(1, props.totalPaginas));
const pagina = ref(Math.min(Math.max(1, props.posicaoInicial), total.value));

// Leitura minima: a pagina que o aluno alcanca pela primeira vez segura a
// seta de avancar ate cumprir o tempo. So conta com a aba visivel, e a
// ultima pagina so e registrada (o que conclui o item) depois do tempo dela.
const exigeTempo = computed(() => props.segundosPorPagina > 0 && !props.concluido);
const liberadaAte = ref(exigeTempo.value ? Math.min(Math.max(1, props.posicaoInicial), total.value) : Number.MAX_SAFE_INTEGER);
const decorridoMs = ref(0);

const emLeituraMinima = computed(() => exigeTempo.value && !carregando.value && !erro.value && pagina.value >= liberadaAte.value);
const restante = computed(() => Math.max(0, Math.ceil(props.segundosPorPagina - decorridoMs.value / 1000)));
// Para leitor de tela: anuncia em degraus de 10 s (anunciar a cada segundo seria ruido).
const restanteAnunciado = computed(() => Math.ceil(restante.value / 10) * 10);
const fracaoRestante = computed(() => Math.max(0, 1 - decorridoMs.value / (props.segundosPorPagina * 1000)));

const zoomPercentual = computed(() => Math.round((zoom.value / ZOOM_BASE) * 100));
const temAnterior = computed(() => pagina.value > 1);
const temProxima = computed(() => pagina.value < total.value && !emLeituraMinima.value);

let relogio = null;
// O tempo vem de timestamps (aba em segundo plano atrasa o intervalo, mas nao o relogio): `marca` e
// o inicio do trecho visivel em curso e `acumulado` o que ja foi contado nesta pagina.
let marca = null;
let acumulado = 0;

function zerarContagem() {
  marca = null;
  acumulado = 0;
  decorridoMs.value = 0;
}

function contar() {
  const agora = Date.now();
  if (!emLeituraMinima.value || document.hidden) {
    if (marca !== null) {
      acumulado += agora - marca;
      marca = null;
    }
    return;
  }

  marca ??= agora;
  decorridoMs.value = acumulado + (agora - marca);
  if (decorridoMs.value < props.segundosPorPagina * 1000) return;

  zerarContagem();
  if (pagina.value < total.value) {
    liberadaAte.value = Math.min(pagina.value + 1, total.value);
  } else {
    liberadaAte.value = Number.MAX_SAFE_INTEGER;
    emit('pagina', pagina.value);
  }
}

function deveRegistrar(numero) {
  return !exigeTempo.value || numero < total.value;
}

let documento = null;
let tarefa = null;
let desmontado = false;
let geracao = 0;
let fila = Promise.resolve();

// Renders em serie: cada chamada cancela a anterior, espera ela terminar e so
// entao usa o canvas. Chamadas superadas por uma mais nova nao fazem nada.
function desenhar() {
  const minha = ++geracao;
  tarefa?.cancel();

  fila = fila.then(async () => {
    if (minha !== geracao || desmontado || !documento || !canvas.value) return;
    try {
      tarefa = await renderizarPagina(documento, pagina.value, canvas.value, zoom.value);
      await tarefa.promise;
    } catch (e) {
      if (e?.name !== 'RenderingCancelledException' && minha === geracao && !desmontado) erro.value = true;
    }
  });

  return fila;
}

function irPara(numero) {
  pagina.value = Math.min(Math.max(1, numero), total.value);
}

function ajustarZoom(delta) {
  zoom.value = Math.min(ZOOM_MAX, Math.max(ZOOM_MIN, Math.round((zoom.value + delta) * 10) / 10));
}

onMounted(async () => {
  let aberto = null;
  try {
    aberto = await abrirDocumento(props.url);
    if (desmontado) {
      aberto.destroy();
      return;
    }
    documento = aberto;
    total.value = documento.numPages;
    pagina.value = Math.min(pagina.value, total.value);
    liberadaAte.value = Math.min(liberadaAte.value, total.value);
    carregando.value = false;
    if (deveRegistrar(pagina.value)) emit('pagina', pagina.value);
    relogio = window.setInterval(contar, TICK_MS);
    document.addEventListener('visibilitychange', contar);
    await desenhar();
  } catch {
    if (desmontado) return;
    erro.value = true;
    carregando.value = false;
  }
});

watch(pagina, (n) => {
  zerarContagem();
  if (deveRegistrar(n)) emit('pagina', n);
  desenhar();
});
watch(zoom, desenhar);

onBeforeUnmount(() => {
  window.clearInterval(relogio);
  document.removeEventListener('visibilitychange', contar);
  desmontado = true;
  geracao++;
  tarefa?.cancel();
  documento?.destroy();
});
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col">
    <div class="flex flex-wrap items-center justify-center gap-3 bg-slate-700 px-3 py-2 text-xs text-white">
      <button type="button" class="rounded px-2 py-1 hover:bg-slate-600 disabled:opacity-40" :disabled="!temAnterior" aria-label="Pagina anterior" @click="irPara(pagina - 1)">&lsaquo;</button>
      <span>pag. {{ pagina }} / {{ total }}</span>
      <button type="button" class="rounded px-2 py-1 hover:bg-slate-600 disabled:opacity-40" :disabled="!temProxima" aria-label="Proxima pagina" @click="irPara(pagina + 1)">&rsaquo;</button>
      <span
        v-if="emLeituraMinima"
        class="relative inline-flex h-7 w-7 items-center justify-center"
        role="timer"
        :aria-label="`Leitura mínima: faltam ${restante} segundos nesta página`"
        :title="pagina < total ? `Avance em ${restante}s` : `Conclui em ${restante}s`"
      >
        <svg class="absolute inset-0 h-7 w-7 -rotate-90" viewBox="0 0 22 22" aria-hidden="true">
          <circle cx="11" cy="11" :r="ANEL_RAIO" fill="none" stroke-width="2.5" class="stroke-slate-500" />
          <circle
            cx="11"
            cy="11"
            :r="ANEL_RAIO"
            fill="none"
            stroke-width="2.5"
            stroke-linecap="round"
            class="anel-leitura stroke-amber-400"
            :stroke-dasharray="ANEL_CIRCUNFERENCIA"
            :stroke-dashoffset="ANEL_CIRCUNFERENCIA * (1 - fracaoRestante)"
          />
        </svg>
        <span class="relative text-[10px] font-bold tabular-nums">{{ restante }}</span>
      </span>
      <span v-if="emLeituraMinima" class="text-amber-200" aria-hidden="true">
        Leitura mínima: aguarde {{ restante }} s para {{ pagina < total ? 'avançar' : 'concluir' }}
      </span>
      <span v-if="emLeituraMinima" class="sr-only" role="status" aria-live="polite">
        Leitura mínima: faltam cerca de {{ restanteAnunciado }} segundos para {{ pagina < total ? 'avançar' : 'concluir' }}.
      </span>
      <span class="hidden h-4 w-px bg-slate-500 sm:inline-block" />
      <button type="button" class="rounded px-2 py-1 hover:bg-slate-600 disabled:opacity-40" :disabled="zoom <= ZOOM_MIN" aria-label="Diminuir zoom" @click="ajustarZoom(-ZOOM_PASSO)">&minus;</button>
      <span>{{ zoomPercentual }}%</span>
      <button type="button" class="rounded px-2 py-1 hover:bg-slate-600 disabled:opacity-40" :disabled="zoom >= ZOOM_MAX" aria-label="Aumentar zoom" @click="ajustarZoom(ZOOM_PASSO)">+</button>
    </div>

    <div class="relative flex min-h-[420px] flex-1 justify-center overflow-auto bg-slate-500 p-4 dark:bg-slate-900">
      <p v-if="carregando" class="self-center text-sm text-white">Carregando o PDF...</p>
      <p v-else-if="erro" class="self-center text-sm text-white">Nao foi possivel exibir este PDF. Use o botao Baixar PDF.</p>
      <canvas v-show="!carregando && !erro" ref="canvas" class="h-fit bg-white shadow-lg" />
    </div>
  </div>
</template>

<style scoped>
.anel-leitura {
  transition: stroke-dashoffset 250ms linear;
}

@media (prefers-reduced-motion: reduce) {
  .anel-leitura {
    transition: none;
  }
}
</style>
