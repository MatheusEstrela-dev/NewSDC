<script setup>
/**
 * Envia, troca ou remove uma imagem de capa sobre a propria imagem (capa do
 * curso, foto da pasta de categoria). Recebe os enderecos de envio e remocao;
 * o backend valida tipo e tamanho, aqui so se evita mandar o que ja se sabe
 * que sera recusado.
 */
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  temCapa: { type: Boolean, default: false },
  urlEnviar: { type: String, required: true },
  urlRemover: { type: String, required: true },
  // Nome do que recebe a capa (ex.: "Cursos livres"), para o leitor de tela distinguir os botoes.
  rotulo: { type: String, default: '' },
});

const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
const TAMANHO_MAXIMO = 10 * 1024 * 1024;

const { show: toast } = useToast();
const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();
const entrada = ref(null);
const enviando = ref(false);

function escolher() {
  entrada.value?.click();
}

function enviar(evento) {
  const arquivo = evento.target.files?.[0];
  evento.target.value = '';
  if (!arquivo) return;

  if (!TIPOS.includes(arquivo.type)) {
    toast('Use uma imagem JPG, PNG ou WebP.', 'error');
    return;
  }
  if (arquivo.size > TAMANHO_MAXIMO) {
    toast('A imagem pode ter no máximo 10 MB.', 'error');
    return;
  }

  enviando.value = true;
  router.post(props.urlEnviar, { capa: arquivo }, {
    forceFormData: true,
    preserveScroll: true,
    onError: (errors) => toast(errors.capa || 'Não foi possível enviar a imagem.', 'error'),
    onFinish: () => { enviando.value = false; },
  });
}

function executarRemocao() {
  enviando.value = true;
  router.delete(props.urlRemover, {
    preserveScroll: true,
    onSuccess: () => toast('Imagem de capa removida.', 'success'),
    onError: (errors) => toast(errors.capa || 'Não foi possível remover a imagem.', 'error'),
    onFinish: () => { enviando.value = false; },
  });
}

function remover() {
  pedirConfirmacao({
    title: 'Remover capa',
    message: props.rotulo ? `Remover a imagem de capa de "${props.rotulo}"?` : 'Remover a imagem de capa?',
    description: 'A capa volta ao visual padrão. Você pode enviar outra imagem depois.',
    variant: 'danger',
    confirmText: 'Remover capa',
  }, executarRemocao);
}

const sufixo = computed(() => (props.rotulo ? ` de ${props.rotulo}` : ''));

const classeBotao = 'inline-flex min-h-[44px] min-w-[44px] items-center justify-center gap-1 rounded-md bg-slate-900/60 px-2 py-1 text-xs font-semibold text-white backdrop-blur transition hover:bg-slate-900/80 disabled:opacity-60 sm:min-h-0 sm:min-w-0';
</script>

<template>
  <div class="flex items-center gap-1.5">
    <input ref="entrada" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="enviar" />
    <button type="button" :class="classeBotao" :disabled="enviando" :title="temCapa ? 'Trocar imagem de capa' : 'Enviar imagem de capa'" :aria-label="`${temCapa ? 'Trocar capa' : 'Enviar capa'}${sufixo}`" @click="escolher">
      <PhotoIcon class="h-4 w-4" />
      {{ enviando ? 'Enviando...' : (temCapa ? 'Trocar capa' : 'Enviar capa') }}
    </button>
    <button v-if="temCapa" type="button" :class="classeBotao" :disabled="enviando" title="Remover imagem de capa" :aria-label="`Remover capa${sufixo}`" @click="remover">
      <TrashIcon class="h-4 w-4" aria-hidden="true" />
    </button>
    <ConfirmDialog :is-open="confirmacao.aberto" v-bind="confirmacao.opcoes" @confirm="confirmar" @cancel="cancelar" />
  </div>
</template>
