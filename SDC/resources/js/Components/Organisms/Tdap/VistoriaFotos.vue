<template>
  <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
    <div class="flex items-center justify-between gap-3 mb-4">
      <div>
        <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Fotos da vistoria</h3>
        <p class="text-xs text-slate-400 mt-0.5">
          {{ fotos.length }} {{ fotos.length === 1 ? 'foto anexada' : 'fotos anexadas' }}
        </p>
      </div>

      <!-- No celular o caminho util e a camera, nao o gerenciador de arquivos:
           o vistoriador esta na frente do caminhao. O botao so aparece onde
           `capture` tem efeito. -->
      <Button
        v-if="podeEditar"
        variant="primary"
        size="md"
        type="button"
        :icon="CameraIcon"
        icon-position="left"
        :disabled="enviando"
        @click="cameraVisivel = !cameraVisivel"
      >
        {{ cameraVisivel ? 'Fechar câmera' : 'Tirar foto' }}
      </Button>
    </div>

    <CameraCapture
      v-if="podeEditar && cameraVisivel"
      class="mb-5"
      :prefixo-do-nome="`vistoria-${vistoriaId}`"
      @captura="(arquivo) => enviar([arquivo])"
    />

    <DropZone
      v-if="podeEditar && !cameraVisivel"
      class="mb-5"
      title="Arraste as fotos aqui"
      :subtitle="isMobile ? 'Ou toque para escolher da galeria (JPG, PNG, WEBP ou HEIC)' : 'JPG, PNG, WEBP ou HEIC — até 10 por vez, 15 MB cada'"
      accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
      @files-selected="enviar"
    />

    <p v-if="erro" class="mb-4 text-sm text-red-600 dark:text-red-400">{{ erro }}</p>

    <div v-if="enviando" class="mb-4 text-sm text-slate-500">Enviando...</div>

    <div v-if="fotos.length" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
      <figure
        v-for="foto in fotos"
        :key="foto.id"
        class="relative group rounded-lg overflow-hidden border border-slate-200 dark:border-slate-700/60 bg-slate-50 dark:bg-slate-800"
      >
        <a :href="urlDaFoto(foto)" target="_blank" rel="noopener">
          <img
            :src="urlDaFoto(foto)"
            :alt="foto.nome_original"
            loading="lazy"
            class="w-full aspect-square object-cover"
          />
        </a>

        <figcaption class="px-2 py-1.5 text-[11px] text-slate-500 dark:text-slate-400 truncate" :title="foto.nome_original">
          {{ foto.nome_original }}
        </figcaption>

        <button
          v-if="podeEditar"
          type="button"
          class="absolute top-1.5 right-1.5 p-1.5 rounded-lg bg-slate-900/70 text-white opacity-0 group-hover:opacity-100 focus:opacity-100 transition-opacity"
          title="Remover foto"
          @click="remover(foto)"
        >
          <TrashIcon class-name="w-4 h-4" />
        </button>
      </figure>
    </div>

    <p v-else class="text-sm text-slate-400">Nenhuma foto anexada.</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import Button from '@/Components/Atoms/Button/Button.vue';
import DropZone from '@/Components/Molecules/Upload/DropZone.vue';
import CameraCapture from '@/Components/Molecules/CameraCapture.vue';
import CameraIcon from '@/Components/Icons/CameraIcon.vue';
import TrashIcon from '@/Components/Icons/TrashIcon.vue';
import { useMobile } from '@/Composables/useMobile';

const props = defineProps({
  vistoriaId: { type: [Number, String], required: true },
  fotos: { type: Array, default: () => [] },
  podeEditar: { type: Boolean, default: false },
});

const { isMobile } = useMobile();

const enviando = ref(false);
const erro = ref('');

// A camera fica embutida na pagina, como o QrScanner do Plantao: o vistoriador
// fotografa e a foto sobe na hora, sem sair da vistoria. Serve tambem no
// desktop com webcam, entao nao ha ramo por plataforma aqui.
const cameraVisivel = ref(false);

// O disco 'tdap' e privado: a miniatura vem por rota, nao por URL de storage.
const urlDaFoto = (foto) => route('tdap.frota.vistorias.fotos.show', [props.vistoriaId, foto.id]);

/**
 * Recarrega SO a prop `fotos`. A grade se atualiza sem recarregar a tela
 * inteira -- e sem perder a posicao de rolagem de quem estava no meio do
 * checklist.
 */
const recarregarFotos = () => router.reload({ only: ['fotos'] });

const enviar = (arquivos) => {
  const lista = Array.from(arquivos ?? []);

  if (!lista.length || enviando.value) {
    return;
  }

  erro.value = '';
  enviando.value = true;

  router.post(
    route('tdap.frota.vistorias.fotos.store', props.vistoriaId),
    { fotos: lista },
    {
      forceFormData: true,
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => recarregarFotos(),
      onError: (erros) => {
        erro.value = Object.values(erros)[0] ?? 'Nao foi possivel enviar as fotos.';
      },
      onFinish: () => {
        enviando.value = false;
      },
    },
  );
};

const remover = (foto) => {
  if (!window.confirm(`Remover a foto "${foto.nome_original}"?`)) {
    return;
  }

  router.delete(route('tdap.frota.vistorias.fotos.destroy', [props.vistoriaId, foto.id]), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => recarregarFotos(),
  });
};
</script>
