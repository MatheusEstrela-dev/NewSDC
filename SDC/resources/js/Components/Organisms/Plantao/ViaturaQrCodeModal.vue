<script setup>
/**
 * Etiqueta do chaveiro. Segue o BeneficiarioQrCodeModal do Cisterna: SVG
 * inline (escala sem borrar no papel, sem segunda requisicao), impressao numa
 * janela propria e download em PNG.
 *
 * A rotacao do token fica atras de confirmacao porque e destrutiva no mundo
 * fisico: toda etiqueta ja colada naquele chaveiro para de funcionar.
 */
import Modal from '@/Components/Modal.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import axios from 'axios';
import { ref, watch } from 'vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Precisa de `id`, `prefixo` e `placa`. */
  viatura: { type: Object, default: null },
});

defineEmits(['close']);

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';
const BOTAO_PERIGO = 'rounded-md border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-300 dark:hover:bg-red-900/20';

const carregando = ref(false);
const erro = ref('');
const dados = ref(null);
const confirmandoRotacao = ref(false);
const rotacionando = ref(false);

/**
 * Le a etiqueta vigente. Somente leitura -- a troca vive em rotacionar().
 */
async function carregar() {
  if (!props.viatura?.id) return;

  carregando.value = true;
  erro.value = '';
  dados.value = null;

  let url;

  try {
    url = route('plantao.viaturas.qrcode', props.viatura.id);
  } catch {
    erro.value = 'Esta aba foi carregada antes desta funcionalidade existir. Recarregue a pagina.';
    carregando.value = false;

    return;
  }

  try {
    const { data } = await axios.get(url, { headers: { Accept: 'application/json' } });
    dados.value = data;
  } catch (e) {
    erro.value = e.response
      ? (e.response.data?.message ?? `Nao foi possivel gerar a etiqueta (HTTP ${e.response.status}).`)
      : 'Falha de rede ao gerar a etiqueta.';
  } finally {
    carregando.value = false;
  }
}

/**
 * Troca a etiqueta. POST porque o ato mata o adesivo vigente: em GET, um
 * prefetch ou um recarregamento repetiria a queima sem ninguem pedir.
 *
 * `rotacionando` bloqueia o segundo clique -- dois disparos gerariam dois
 * tokens, e o adesivo impresso a partir do primeiro ja nasceria morto.
 */
async function rotacionar() {
  if (!props.viatura?.id || rotacionando.value) return;

  rotacionando.value = true;
  erro.value = '';

  try {
    const { data } = await axios.post(route('plantao.viaturas.qrcode.rotacionar', props.viatura.id));
    dados.value = data;
    confirmandoRotacao.value = false;
  } catch (e) {
    // A recusa de dominio ("a viatura esta na rua com Fulano") vem pronta.
    erro.value = e.response
      ? (e.response.data?.message ?? `Nao foi possivel trocar a etiqueta (HTTP ${e.response.status}).`)
      : 'Falha de rede ao trocar a etiqueta.';
    confirmandoRotacao.value = false;
  } finally {
    rotacionando.value = false;
  }
}

function imprimir() {
  const janela = window.open('', '_blank', 'width=420,height=560');

  if (!janela) return;

  janela.document.write(`
    <html>
      <head><title>Chave — ${dados.value.prefixo} ${dados.value.placa}</title></head>
      <body style="font-family: sans-serif; text-align: center; padding: 24px;">
        <!-- O SVG do endroid traz width/height fixos; sem forcar 100% ele
             estoura a caixa, como estoura no modal. -->
        <style>svg { width: 100%; height: auto; }</style>
        <div style="width: 260px; margin: 0 auto;">${dados.value.svg}</div>
        <p style="font-family: monospace; font-size: 20px; margin-top: 12px;">
          ${dados.value.prefixo} · ${dados.value.placa}
        </p>
        <p style="font-size: 12px; color: #444;">${dados.value.modelo ?? ''}</p>
      </body>
    </html>
  `);
  janela.document.close();
  janela.focus();
  janela.print();
}

watch(
  () => [props.show, props.viatura?.id],
  ([aberto]) => {
    if (aberto) {
      carregar();
    } else {
      confirmandoRotacao.value = false;
      erro.value = '';
    }
  },
);
</script>

<template>
  <Modal :show="show" max-width="md" @close="$emit('close')">
    <div class="bg-white dark:bg-slate-900">
      <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-700/50">
        <div class="min-w-0">
          <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Etiqueta da chave</h3>
          <p class="truncate text-sm text-slate-500 dark:text-slate-400">
            {{ viatura?.prefixo }} - {{ viatura?.placa || '—' }}
          </p>
        </div>

        <button
          type="button"
          class="rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
          aria-label="Fechar"
          @click="$emit('close')"
        >
          <XMarkIcon class="h-5 w-5" />
        </button>
      </div>

      <div class="p-6">
        <p v-if="carregando" class="py-10 text-center text-sm text-slate-500">Gerando a etiqueta...</p>

        <!--
          O erro NAO substitui o QR quando ja existe etiqueta carregada: uma
          troca recusada ("a viatura esta na rua") deixaria a tela vazia, dando
          a entender que a etiqueta vigente sumiu junto. Ela continua valida e
          continua sendo a que precisa estar colada no chaveiro.
        -->
        <div
          v-if="!carregando && erro"
          class="mb-4 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700 dark:border-amber-900/40 dark:bg-amber-900/20 dark:text-amber-300"
        >
          {{ erro }}
        </div>

        <div v-if="!carregando && dados" class="text-center">
          <!--
            `[&_svg]:w-full` e obrigatorio: o endroid escreve width/height fixos
            (300px) no proprio SVG e sem forcar ele ignora a caixa do modal.
          -->
          <div
            class="mx-auto w-56 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 [&_svg]:h-auto [&_svg]:w-full"
            v-html="dados.svg"
          />

          <p class="mt-3 font-mono text-sm text-slate-700 dark:text-slate-300">
            {{ dados.prefixo }} · {{ dados.placa }}
          </p>

          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Cole no chaveiro. O agente le esta etiqueta para retirar e devolver a chave — sempre a mesma,
            para qualquer reserva.
          </p>

          <div
            v-if="confirmandoRotacao"
            class="mt-4 rounded-md border border-red-200 bg-red-50 px-3 py-3 text-left text-sm text-red-700 dark:border-red-900/40 dark:bg-red-900/20 dark:text-red-300"
          >
            <p class="font-semibold">Gerar uma etiqueta nova?</p>
            <p class="mt-1 text-xs">
              Toda etiqueta ja colada neste chaveiro para de funcionar. Use apenas se a etiqueta antiga
              foi extraviada.
            </p>
            <div class="mt-3 flex gap-2">
              <button type="button" :class="BOTAO_SEC" :disabled="rotacionando" @click="confirmandoRotacao = false">
                Cancelar
              </button>
              <!-- disabled durante o POST: dois cliques gerariam dois tokens, e
                   o adesivo impresso a partir do primeiro ja nasceria morto. -->
              <button type="button" :class="BOTAO_PERIGO" :disabled="rotacionando" @click="rotacionar">
                {{ rotacionando ? 'Trocando...' : 'Confirmar troca' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="!carregando && dados && !confirmandoRotacao"
        class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700/50"
      >
        <button type="button" :class="BOTAO_PERIGO" @click="confirmandoRotacao = true">Trocar etiqueta</button>
        <button type="button" :class="BOTAO_SEC" @click="imprimir">Imprimir</button>
        <!-- `download` explicito: a rota serve a etiqueta como inline, para que
             abrir a URL direto MOSTRE o QR numa tela. Aqui a intencao e salvar
             o arquivo, e o atributo e que garante isso. -->
        <a :href="dados.download" download :class="BOTAO">Baixar PNG</a>
      </div>
    </div>
  </Modal>
</template>
