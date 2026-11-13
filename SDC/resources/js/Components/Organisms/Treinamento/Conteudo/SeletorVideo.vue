<script setup>
import { onUnmounted, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import ZonaArquivoVideo from '@/Components/Organisms/Treinamento/Conteudo/ZonaArquivoVideo.vue';
import { formatarDuracao } from '@/Composables/treinamento/formatarDuracao';
import { mensagemDeErro } from '@/Composables/treinamento/useUploadEmPartes';

const DEBOUNCE_MS = 300;

const props = defineProps({
  show: { type: Boolean, default: false },
  treinamentoId: { type: Number, required: true },
  item: { type: Object, default: null },
});

const emit = defineEmits(['vinculado', 'fechar']);

const aba = ref('biblioteca');
const erro = ref(null);
const ocupado = ref(false);

// ---- Biblioteca -------------------------------------------------------------

const busca = ref('');
const videos = ref([]);
const maisPaginas = ref(false);
const carregando = ref(false);
let debounce = null;
let consulta = null;
// Registro criado nesta sessao do modal; um reenvio so repete o vinculo.
let videoCriadoId = null;

async function carregar() {
  if (!props.show) return;
  consulta?.abort();
  const propria = new AbortController();
  consulta = propria;
  carregando.value = true;
  try {
    const { data } = await window.axios.get(route('treinamentos.professor.videos.index'), {
      params: { q: busca.value || undefined, prontos: 1 },
      signal: propria.signal,
    });
    videos.value = data.data;
    maisPaginas.value = data.last_page > 1;
  } catch (e) {
    if (!propria.signal.aborted) erro.value = mensagemDeErro(e);
  } finally {
    // Abortada por uma consulta mais nova: ela e dona do flag.
    if (consulta === propria) carregando.value = false;
  }
}

watch(busca, () => {
  clearTimeout(debounce);
  if (!props.show) return;
  debounce = setTimeout(carregar, DEBOUNCE_MS);
});

// `envio` ({ videoId, arquivo }) vai junto quando o arquivo foi escolhido aqui:
// o card do item comeca o envio sozinho.
async function vincular(videoId, envio = null) {
  erro.value = null;
  ocupado.value = true;
  try {
    await window.axios.put(route('treinamentos.professor.itens.video.vincular', [props.treinamentoId, props.item.id]), { video_id: videoId });
    emit('vinculado', props.item, envio);
    emit('fechar');
  } catch (e) {
    erro.value = mensagemDeErro(e);
  } finally {
    ocupado.value = false;
  }
}

// ---- Enviar novo ------------------------------------------------------------

// Com o arquivo ja escolhido: cria o registro, vincula ao item e entrega o
// arquivo ao card do item, que faz o envio em partes. Nunca cria video vazio.
async function criarNovo(arquivo) {
  if (ocupado.value) return;
  erro.value = null;
  ocupado.value = true;
  try {
    if (videoCriadoId === null) {
      const { data: criado } = await window.axios.post(route('treinamentos.professor.videos.store'), { titulo: props.item.titulo });
      videoCriadoId = criado.id;
    }
    await vincular(videoCriadoId, { videoId: videoCriadoId, arquivo });
  } catch (e) {
    erro.value = mensagemDeErro(e);
    ocupado.value = false;
  }
}

// ---- Ciclo do modal ---------------------------------------------------------

function limpar() {
  clearTimeout(debounce);
  consulta?.abort();
  carregando.value = false;
  aba.value = 'biblioteca';
  busca.value = '';
  videos.value = [];
  erro.value = null;
  ocupado.value = false;
  videoCriadoId = null;
}

watch(() => props.show, (aberto) => {
  limpar();
  if (aberto) carregar();
});

onUnmounted(limpar);
</script>

<template>
  <Modal :show="show" max-width="2xl" @close="emit('fechar')">
    <div v-if="show" class="space-y-4 p-6">
      <Heading :level="3" class="text-lg font-semibold">Vídeo do item</Heading>

      <div class="flex gap-2 border-b border-slate-200 dark:border-slate-700">
        <button
          v-for="opcao in [{ id: 'biblioteca', rotulo: 'Biblioteca' }, { id: 'novo', rotulo: 'Enviar novo' }]"
          :key="opcao.id"
          type="button"
          class="-mb-px border-b-2 px-3 py-2 text-sm font-medium"
          :class="aba === opcao.id ? 'border-sky-600 text-sky-700 dark:text-sky-300' : 'border-transparent text-slate-500'"
          @click="aba = opcao.id"
        >{{ opcao.rotulo }}</button>
      </div>

      <p v-if="erro" class="text-sm text-red-600">{{ erro }}</p>

      <div v-show="aba === 'biblioteca'" class="space-y-3">
        <TextInput v-model="busca" class="w-full" placeholder="Buscar por título" />
        <Text v-if="carregando" size="xs" color="muted">Carregando...</Text>
        <Text v-else-if="videos.length === 0" color="muted">Nenhum vídeo pronto encontrado.</Text>
        <ul v-else class="max-h-80 space-y-2 overflow-y-auto">
          <li
            v-for="video in videos"
            :key="video.id"
            class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 dark:border-slate-700"
          >
            <div class="min-w-0 flex-1">
              <Text class="block truncate font-medium">{{ video.titulo }}</Text>
              <Text size="xs" color="muted">
                {{ formatarDuracao(video.duracao_segundos) }}
                <template v-if="video.dono"> - {{ video.dono }}</template>
                <template v-if="video.em_uso"> - em uso em outros itens</template>
              </Text>
            </div>
            <Button size="sm" :disabled="ocupado" @click="vincular(video.id)">Usar este vídeo</Button>
          </li>
        </ul>
        <Text v-if="maisPaginas" size="xs" color="muted">Há mais vídeos: refine a busca.</Text>
      </div>

      <div v-show="aba === 'novo'" class="space-y-3">
        <Text color="muted">
          Escolha o arquivo: o vídeo é criado com o título do item ("{{ item.titulo }}"), vinculado a ele
          e o envio começa no próprio item.
        </Text>
        <ZonaArquivoVideo :desabilitada="ocupado" @arquivo="criarNovo" @erro="erro = $event" />
        <Text v-if="item.video" size="xs" color="muted">
          Para reenviar o arquivo deste mesmo vídeo, mantendo o vínculo e o histórico, feche e use
          "Substituir arquivo" no item.
        </Text>
      </div>

      <div class="flex justify-end">
        <Button type="button" variant="outline" @click="emit('fechar')">Fechar</Button>
      </div>
    </div>
  </Modal>
</template>
