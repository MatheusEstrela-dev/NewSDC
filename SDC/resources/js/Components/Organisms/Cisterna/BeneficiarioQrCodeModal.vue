<template>
  <Modal :show="show" max-width="md" @close="$emit('close')">
    <div class="bg-white dark:bg-slate-900">
      <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-700/50">
        <div class="min-w-0">
          <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">QR Code da cisterna</h3>
          <p class="truncate text-sm text-slate-500 dark:text-slate-400">
            {{ beneficiario?.nome || '—' }}
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
        <p v-if="carregando" class="py-10 text-center text-sm text-slate-500">Gerando o QR Code...</p>

        <div v-else-if="erro" class="py-8 text-center">
          <p class="text-sm text-amber-600 dark:text-amber-400">{{ erro }}</p>
        </div>

        <div v-else-if="dados" class="text-center">
          <!--
            SVG inline, e nao <img>: escala sem borrar em qualquer tamanho e na
            impressao, e nao custa uma segunda requisicao. Vem do endroid, pelo
            mesmo servico que gera o adesivo.

            `[&_svg]:w-full` e obrigatorio: o endroid escreve width/height fixos
            no proprio SVG (300px), e sem forcar ele ignora a caixa e transborda
            para fora dela -- foi o que deixou o modal torto.
          -->
          <div
            class="mx-auto w-56 rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-700 [&_svg]:h-auto [&_svg]:w-full"
            v-html="dados.svg"
          />

          <p class="mt-3 font-mono text-sm text-slate-700 dark:text-slate-300">
            Nº de instalacao {{ dados.numero_instalacao }}
          </p>
          <p class="mt-1 break-all text-xs text-slate-400">{{ dados.url }}</p>

          <p class="mt-3 text-xs text-slate-500 dark:text-slate-400">
            Ao ler o adesivo, o celular abre a ficha publica desta cisterna — sem exigir login.
          </p>
        </div>
      </div>

      <div
        v-if="!carregando && dados"
        class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4 dark:border-slate-700/50"
      >
        <!--
          Download em PNG pela rota que ja existia. Nao ha PDF aqui: o NewSDC
          nao tem biblioteca de PDF, e o caminho para papel e imprimir a pagina
          -- o dialogo do navegador oferece "Salvar como PDF", que e como as
          demais fichas do sistema viram PDF.
        -->
        <button type="button" :class="BOTAO_SEC" @click="imprimir">Imprimir</button>
        <a :href="dados.download" :class="BOTAO">Baixar PNG</a>
      </div>
    </div>
  </Modal>
</template>

<script setup>
import { ref, watch } from 'vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  show: { type: Boolean, default: false },
  /** Precisa de `id` e `nome`. */
  beneficiario: { type: Object, default: null },
});

defineEmits(['close']);

const BOTAO = 'rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700';
const BOTAO_SEC = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

const carregando = ref(false);
const erro = ref('');
const dados = ref(null);

async function carregar() {
  if (!props.beneficiario?.id) return;

  carregando.value = true;
  erro.value = '';
  dados.value = null;

  let url;

  try {
    url = route('cisternas.beneficiarios.qrcode', props.beneficiario.id);
  } catch {
    erro.value = 'Esta aba foi carregada antes desta funcionalidade existir. Recarregue a pagina.';
    carregando.value = false;

    return;
  }

  try {
    const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
    const corpo = await resposta.json().catch(() => null);

    if (!resposta.ok) {
      // O 422 explica que falta numero de instalacao e por que. Substituir por
      // texto generico esconderia o unico caminho para resolver.
      erro.value = corpo?.message ?? `Nao foi possivel gerar o QR Code (HTTP ${resposta.status}).`;

      return;
    }

    dados.value = corpo;
  } catch {
    erro.value = 'Falha de rede ao gerar o QR Code.';
  } finally {
    carregando.value = false;
  }
}

/**
 * Imprime so o QR, e nao a listagem inteira atras do modal.
 *
 * Abre uma janela com o adesivo montado -- QR, numero e nome -- porque mandar
 * window.print() daqui levaria a pagina toda para o papel.
 */
function imprimir() {
  const janela = window.open('', '_blank', 'width=420,height=560');

  if (!janela) return;

  janela.document.write(`
    <html>
      <head><title>QR Code — instalacao ${dados.value.numero_instalacao}</title></head>
      <body style="font-family: sans-serif; text-align: center; padding: 24px;">
        <!-- O SVG do endroid traz width/height fixos; sem forcar 100% ele
             estoura a caixa aqui tambem, como estourava no modal. -->
        <style>svg { width: 100%; height: auto; }</style>
        <div style="width: 260px; margin: 0 auto;">${dados.value.svg}</div>
        <p style="font-family: monospace; font-size: 18px; margin-top: 12px;">
          Nº ${dados.value.numero_instalacao}
        </p>
        <p style="font-size: 12px; color: #444;">${dados.value.beneficiario}</p>
      </body>
    </html>
  `);
  janela.document.close();
  janela.focus();
  janela.print();
}

watch(
  () => [props.show, props.beneficiario?.id],
  ([aberto]) => {
    if (aberto) carregar();
  },
);
</script>
