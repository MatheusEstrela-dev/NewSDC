<script setup>
/**
 * Troca ou remove a imagem de capa do curso, sobre a propria capa do card.
 * O backend valida tipo e tamanho; aqui so se evita mandar o que ja se sabe
 * que sera recusado.
 */
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
  curso: { type: Object, required: true }, // { id, capa_url }
});

const TIPOS = ['image/jpeg', 'image/png', 'image/webp'];
const TAMANHO_MAXIMO = 2 * 1024 * 1024;

const { show: toast } = useToast();
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
    toast('A imagem pode ter no máximo 2 MB.', 'error');
    return;
  }

  enviando.value = true;
  router.post(route('treinamentos.professor.capa.store', props.curso.id), { capa: arquivo }, {
    forceFormData: true,
    preserveScroll: true,
    onError: (errors) => toast(errors.capa || 'Não foi possível enviar a imagem.', 'error'),
    onFinish: () => { enviando.value = false; },
  });
}

function remover() {
  enviando.value = true;
  router.delete(route('treinamentos.professor.capa.destroy', props.curso.id), {
    preserveScroll: true,
    onFinish: () => { enviando.value = false; },
  });
}

const classeBotao = 'inline-flex items-center gap-1 rounded-md bg-slate-900/60 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur transition hover:bg-slate-900/80 disabled:opacity-60';
</script>

<template>
  <div class="flex items-center gap-1.5">
    <input ref="entrada" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="enviar" />
    <button type="button" :class="classeBotao" :disabled="enviando" :title="curso.capa_url ? 'Trocar imagem de capa' : 'Enviar imagem de capa'" @click="escolher">
      <PhotoIcon class="h-4 w-4" />
      {{ enviando ? 'Enviando...' : (curso.capa_url ? 'Trocar capa' : 'Enviar capa') }}
    </button>
    <button v-if="curso.capa_url" type="button" :class="classeBotao" :disabled="enviando" title="Remover imagem de capa" @click="remover">
      <TrashIcon class="h-4 w-4" />
      <span class="sr-only">Remover capa</span>
    </button>
  </div>
</template>
