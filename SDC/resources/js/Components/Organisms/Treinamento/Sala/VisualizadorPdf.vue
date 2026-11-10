<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePdf } from '@/Composables/treinamento/usePdf';

const props = defineProps({
  url: { type: String, required: true },
  posicaoInicial: { type: Number, default: 0 },
  totalPaginas: { type: Number, default: 1 },
});

const emit = defineEmits(['pagina']);

const { abrirDocumento, renderizarPagina } = usePdf();

const ZOOM_MIN = 0.6;
const ZOOM_MAX = 2.4;
const ZOOM_PASSO = 0.2;

const canvas = ref(null);
const carregando = ref(true);
const erro = ref(false);
const zoom = ref(1.2);
const total = ref(Math.max(1, props.totalPaginas));
const pagina = ref(Math.min(Math.max(1, props.posicaoInicial), total.value));

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
    carregando.value = false;
    emit('pagina', pagina.value);
    await desenhar();
  } catch {
    if (desmontado) return;
    erro.value = true;
    carregando.value = false;
  }
});

watch(pagina, (n) => {
  emit('pagina', n);
  desenhar();
});
watch(zoom, desenhar);

onBeforeUnmount(() => {
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
