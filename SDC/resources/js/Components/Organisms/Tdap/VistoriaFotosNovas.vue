<template>
  <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
    <div class="flex items-center justify-between gap-3 mb-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Fotos da vistoria</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          <template v-if="itens.length">
            {{ itens.length }} de {{ maximo }} selecionada{{ itens.length === 1 ? '' : 's' }} — sobem ao registrar a vistoria
          </template>
          <template v-else>
            Opcional. As fotos sobem junto com a ficha, ao registrar.
          </template>
        </p>
      </div>

      <Button
        variant="primary"
        size="md"
        type="button"
        :icon="CameraIcon"
        icon-position="left"
        :disabled="cheio"
        @click="cameraVisivel = !cameraVisivel"
      >
        {{ cameraVisivel ? 'Fechar câmera' : 'Tirar foto' }}
      </Button>
    </div>

    <CameraCapture
      v-if="cameraVisivel && !cheio"
      class="mb-5"
      prefixo-do-nome="vistoria"
      @captura="(arquivo) => adicionar([arquivo])"
    />

    <DropZone
      v-if="!cameraVisivel && !cheio"
      class="mb-5"
      title="Arraste as fotos aqui"
      subtitle="JPG, PNG, WEBP ou HEIC — até 10, 15 MB cada"
      accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
      @files-selected="adicionar"
    />

    <p v-if="cheio" class="mb-4 text-sm text-amber-600 dark:text-amber-400">
      Limite de {{ maximo }} fotos atingido. Remova alguma para trocar, ou anexe o restante depois de registrar.
    </p>

    <p v-if="erro" class="mb-4 text-sm text-red-600 dark:text-red-400">{{ erro }}</p>

    <div v-if="itens.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
      <figure
        v-for="item in itens"
        :key="item.id"
        class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-800"
      >
        <img :src="item.preview" :alt="item.arquivo.name" class="w-full aspect-square object-cover" />

        <figcaption class="px-2 py-1.5 text-[11px] text-slate-500 dark:text-slate-400 truncate" :title="item.arquivo.name">
          {{ item.arquivo.name }}
        </figcaption>

        <button
          type="button"
          class="absolute top-1.5 right-1.5 p-1.5 rounded-lg bg-slate-900/70 text-white opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity"
          title="Remover foto"
          @click="remover(item.id)"
        >
          <TrashIcon class-name="w-4 h-4" />
        </button>
      </figure>
    </div>
  </div>
</template>

<script setup>
/**
 * Fotos de uma vistoria que AINDA NAO EXISTE.
 *
 * Diferente do VistoriaFotos, que fala com a API a cada anexo: aqui nao ha
 * vistoria_id para pendurar nada, entao os arquivos ficam no navegador e sobem
 * no mesmo POST da ficha. O pai recebe a lista por v-model e a entrega ao
 * useForm.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import DropZone from '@/Components/Molecules/Upload/DropZone.vue';
import CameraCapture from '@/Components/Molecules/CameraCapture.vue';
import CameraIcon from '@/Components/Icons/CameraIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';

const props = defineProps({
  /** Lista de File entregue ao formulario. */
  modelValue: { type: Array, default: () => [] },
  /** Mesmo teto do backend (StoreVistoriaFotoRequest::MAX_POR_LOTE). */
  maximo: { type: Number, default: 10 },
  /** Mesmo teto por arquivo do backend: 15 MB. */
  tamanhoMaximoMb: { type: Number, default: 15 },
});

const emit = defineEmits(['update:modelValue']);

const itens = ref([]);
const erro = ref('');
const cameraVisivel = ref(false);

const cheio = computed(() => itens.value.length >= props.maximo);

const TIPOS = ['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif'];

function adicionar(arquivos) {
  erro.value = '';

  const lista = Array.from(arquivos ?? []);
  const recusados = [];

  for (const arquivo of lista) {
    if (itens.value.length >= props.maximo) {
      recusados.push(`${arquivo.name}: limite de ${props.maximo} fotos`);

      continue;
    }

    // Alguns aparelhos entregam heic com type vazio; nesse caso o nome decide.
    const tipoOk = TIPOS.includes(arquivo.type)
      || (!arquivo.type && /\.(jpe?g|png|webp|heic|heif)$/i.test(arquivo.name));

    if (!tipoOk) {
      recusados.push(`${arquivo.name}: formato nao aceito`);

      continue;
    }

    if (arquivo.size > props.tamanhoMaximoMb * 1024 * 1024) {
      recusados.push(`${arquivo.name}: acima de ${props.tamanhoMaximoMb} MB`);

      continue;
    }

    itens.value.push({
      id: `${arquivo.name}-${arquivo.size}-${Date.now()}-${Math.random()}`,
      arquivo,
      preview: URL.createObjectURL(arquivo),
    });
  }

  if (recusados.length) {
    erro.value = recusados.join(' · ');
  }

  sincronizar();
}

function remover(id) {
  const indice = itens.value.findIndex((i) => i.id === id);

  if (indice === -1) {
    return;
  }

  // Sem revoke o blob fica preso na memoria da aba ate o reload -- com dez
  // fotos de camera isso e dezenas de MB.
  URL.revokeObjectURL(itens.value[indice].preview);
  itens.value.splice(indice, 1);

  sincronizar();
}

function sincronizar() {
  emit('update:modelValue', itens.value.map((i) => i.arquivo));
}

// O pai zera a lista depois de um envio bem-sucedido; os previews precisam ir
// junto, senao a grade mostraria fotos que nao estao mais no formulario.
watch(
  () => props.modelValue,
  (valor) => {
    if (valor.length === 0 && itens.value.length > 0) {
      itens.value.forEach((i) => URL.revokeObjectURL(i.preview));
      itens.value = [];
    }
  },
);

onBeforeUnmount(() => {
  itens.value.forEach((i) => URL.revokeObjectURL(i.preview));
});
</script>
