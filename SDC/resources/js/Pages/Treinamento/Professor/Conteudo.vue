<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import draggable from '@/lib/vuedraggable-src/vuedraggable.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import PendenciasPublicacao from '@/Components/Molecules/Treinamento/PendenciasPublicacao.vue';
import MenuCurso from '@/Components/Organisms/Treinamento/Professor/MenuCurso.vue';
import UploaderVideo from '@/Components/Organisms/Treinamento/Conteudo/UploaderVideo.vue';
import SeletorVideo from '@/Components/Organisms/Treinamento/Conteudo/SeletorVideo.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';
import { usePdf } from '@/Composables/treinamento/usePdf';
import { formatarDuracao } from '@/Composables/treinamento/formatarDuracao';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  pastas: { type: Array, default: () => [] },
  pendentes: { type: Number, default: 0 },
});

const { show: toast } = useToast();
const { contarPaginas } = usePdf();

// Copia local (JSON: as props chegam como proxy reativo, que o structuredClone nao aceita): o vuedraggable reordena esta lista; o servidor e a fonte de verdade.
const copiar = (valor) => JSON.parse(JSON.stringify(valor));
const lista = ref(copiar(props.pastas));
watch(() => props.pastas, (novas) => { lista.value = copiar(novas); });

const totalItens = computed(() => lista.value.reduce((soma, p) => soma + p.itens.length, 0));


const erroPublicacao = computed(() => usePage().props.errors?.publicar ?? '');

function avisarErro(erros) {
  toast(Object.values(erros)[0] || 'Nao foi possivel concluir a acao.', 'error');
  lista.value = copiar(props.pastas);
}

// ---- Pastas -------------------------------------------------------------

const pastaAberta = ref(false);
const pastaEmEdicao = ref(null);
const PASTA_VAZIA = { titulo: '', descricao: '', carga_horaria: 0 };
const formPasta = useForm({ ...PASTA_VAZIA });

function abrirPasta(pasta = null) {
  pastaEmEdicao.value = pasta;
  // Apos um envio com sucesso o Inertia assume os valores enviados como defaults:
  // fixa os vazios antes de resetar.
  formPasta.defaults({ ...PASTA_VAZIA }).reset();
  formPasta.clearErrors();
  if (pasta) {
    formPasta.titulo = pasta.titulo;
    formPasta.descricao = pasta.descricao ?? '';
    formPasta.carga_horaria = pasta.carga_horaria ?? 0;
  }
  pastaAberta.value = true;
}

function salvarPasta() {
  const opcoes = {
    preserveScroll: true,
    onSuccess: () => { pastaAberta.value = false; },
  };

  if (pastaEmEdicao.value) {
    formPasta.put(route('treinamentos.professor.pastas.update', [props.curso.id, pastaEmEdicao.value.id]), opcoes);
  } else {
    formPasta.post(route('treinamentos.professor.pastas.store', props.curso.id), opcoes);
  }
}

function excluirPasta(pasta) {
  if (!window.confirm(`Excluir a pasta "${pasta.titulo}" e seus itens?`)) return;
  router.delete(route('treinamentos.professor.pastas.destroy', [props.curso.id, pasta.id]), {
    preserveScroll: true,
    onError: avisarErro,
  });
}

function reordenarPastas() {
  router.post(route('treinamentos.professor.pastas.ordem', props.curso.id), {
    ids: lista.value.map((p) => p.id),
  }, { preserveScroll: true, onError: avisarErro });
}

// ---- Itens --------------------------------------------------------------

const itemAberto = ref(false);
const pastaDoItem = ref(null);
const itemEmEdicao = ref(null);
const lendoPdf = ref(false);
const ITEM_VAZIO = {
  tipo: 'PDF',
  titulo: '',
  descricao: '',
  obrigatorio: true,
  total_paginas: '',
  arquivo: null,
  nota_minima: 70,
  max_tentativas: 3,
};
const formItem = useForm({ ...ITEM_VAZIO });
// Troca a chave para recriar o input file e descartar o arquivo escolhido antes.
const chaveArquivo = ref(0);

const tipoDoItem = computed(() => itemEmEdicao.value?.tipo ?? formItem.tipo);
const ehPdf = computed(() => tipoDoItem.value === 'PDF');
const ehVideo = computed(() => tipoDoItem.value === 'VIDEO');
const ehProva = computed(() => tipoDoItem.value === 'PROVA');

// Prova e sempre obrigatoria. Nota minima e tentativas so seguem na criacao: o
// payload do conteudo nao traz os valores atuais, entao editar o item nao os
// sobrescreve (a configuracao fica na aba Provas). Nos demais tipos nao seguem.
formItem.transform((dados) => {
  const { nota_minima: notaMinima, max_tentativas: maxTentativas, ...resto } = dados;
  if (!ehProva.value) return resto;
  const base = { ...resto, obrigatorio: true };
  return itemEmEdicao.value ? base : { ...base, nota_minima: notaMinima, max_tentativas: maxTentativas };
});

function abrirItem(pasta, item = null) {
  pastaDoItem.value = pasta;
  itemEmEdicao.value = item;
  formItem.defaults({ ...ITEM_VAZIO }).reset();
  formItem.clearErrors();
  chaveArquivo.value++;
  if (item) {
    formItem.tipo = item.tipo;
    formItem.titulo = item.titulo;
    formItem.descricao = item.descricao ?? '';
    formItem.obrigatorio = item.obrigatorio;
    formItem.total_paginas = item.total_paginas ?? '';
  }
  itemAberto.value = true;
}

async function escolherArquivo(evento) {
  const arquivo = evento.target.files?.[0] ?? null;
  formItem.arquivo = arquivo;

  if (arquivo && ehPdf.value) {
    lendoPdf.value = true;
    try {
      formItem.total_paginas = await contarPaginas(arquivo);
    } catch {
      formItem.total_paginas = '';
      formItem.setError('arquivo', 'Nao foi possivel ler o PDF.');
    } finally {
      lendoPdf.value = false;
    }
  }
}

function salvarItem() {
  const opcoes = {
    preserveScroll: true,
    forceFormData: true,
    onSuccess: () => { itemAberto.value = false; },
  };

  if (itemEmEdicao.value) {
    formItem.post(route('treinamentos.professor.itens.update', [props.curso.id, itemEmEdicao.value.id]), opcoes);
  } else {
    formItem.post(route('treinamentos.professor.itens.store', [props.curso.id, pastaDoItem.value.id]), opcoes);
  }
}

function excluirItem(item) {
  if (!window.confirm(`Excluir o item "${item.titulo}"?`)) return;
  router.delete(route('treinamentos.professor.itens.destroy', [props.curso.id, item.id]), {
    preserveScroll: true,
    onError: avisarErro,
  });
}

function reordenarItens(pasta) {
  router.post(route('treinamentos.professor.itens.ordem', [props.curso.id, pasta.id]), {
    ids: pasta.itens.map((i) => i.id),
  }, { preserveScroll: true, onError: avisarErro });
}

// ---- Video do item -------------------------------------------------------

const seletorAberto = ref(false);
const itemDoSeletor = ref(null);

function abrirSeletor(item) {
  itemDoSeletor.value = item;
  seletorAberto.value = true;
}

// O servidor e a fonte do payload do video vinculado.
function recarregarPastas() {
  router.reload({ only: ['pastas'], preserveScroll: true });
}

// Substitui o video em todos os itens que o usam, sem recarregar a pagina.
function atualizarVideo(atualizado) {
  lista.value.forEach((pasta) => pasta.itens.forEach((item) => {
    if (item.video?.id === atualizado.id) item.video = { ...item.video, ...atualizado };
  }));
}

async function desvincularVideo(item) {
  if (!window.confirm(`Desvincular o vídeo do item "${item.titulo}"? O vídeo continua na biblioteca.`)) return;
  try {
    await window.axios.delete(route('treinamentos.professor.itens.video.desvincular', [props.curso.id, item.id]));
    recarregarPastas();
  } catch (e) {
    toast(e.response?.data?.message || 'Não foi possível desvincular o vídeo.', 'error');
  }
}

function avisoDoVideo(video) {
  if (video.status === 'FALHOU') {
    return video.tem_versao ? 'O processamento da nova versão falhou; a versão atual continua disponível para os alunos.' : '';
  }
  if (video.status === 'PRONTO') return '';
  return video.tem_versao
    ? 'Nova versão em processamento; a versão atual continua disponível para os alunos.'
    : 'Em preparação: os alunos só veem o vídeo depois que o processamento terminar.';
}

function publicar() {
  router.post(route('treinamentos.professor.publicar', props.curso.id), {}, {
    preserveScroll: true,
  });
}
</script>

<template>
  <div>
    <PageHeader
      :title="curso.titulo"
      description="Monte as pastas e os itens do curso. Os alunos veem as pastas na ordem abaixo."
      :icon-image="moduleIcon('treinamento')"
      variant="gradient"
    />

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[14rem_1fr]">
      <MenuCurso :curso="curso" ativo="conteudo" :pendentes="pendentes" />

      <div class="min-w-0 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <Text color="muted">{{ lista.length }} pasta(s), {{ totalItens }} item(ns)</Text>
          <div class="flex gap-2">
            <Button size="sm" variant="outline" @click="abrirPasta()">+ Nova pasta</Button>
            <Button size="sm" variant="success" :disabled="curso.publicado" @click="publicar">
              {{ curso.publicado ? 'Publicado' : 'Publicar curso' }}
            </Button>
          </div>
        </div>

        <PendenciasPublicacao :mensagem="erroPublicacao" :href-provas="route('treinamentos.professor.provas', curso.id)" />

        <CardBase v-if="lista.length === 0">
          <Text color="muted">Nenhuma pasta ainda. Crie a primeira com "+ Nova pasta".</Text>
        </CardBase>

        <draggable
          v-model="lista"
          item-key="id"
          handle=".handle-pasta"
          :animation="200"
          class="space-y-4"
          @end="reordenarPastas"
        >
          <template #item="{ element: pasta, index }">
            <CardBase>
              <div class="flex items-start gap-3">
                <span class="handle-pasta cursor-grab select-none text-slate-400" title="Arraste para reordenar">::</span>
                <div class="min-w-0 flex-1">
                  <Heading :level="3" class="text-base font-semibold">{{ index + 1 }}. {{ pasta.titulo }}</Heading>
                  <Text v-if="pasta.descricao" size="xs" color="muted">{{ pasta.descricao }}</Text>
                </div>
                <div class="flex gap-2">
                  <Button size="sm" variant="outline" @click="abrirPasta(pasta)">Editar</Button>
                  <Button size="sm" variant="danger" @click="excluirPasta(pasta)">Excluir</Button>
                </div>
              </div>

              <draggable
                v-model="pasta.itens"
                item-key="id"
                handle=".handle-item"
                :animation="200"
                class="mt-3 space-y-2"
                @end="reordenarItens(pasta)"
              >
                <template #item="{ element: item }">
                  <div class="rounded-md border border-slate-200 dark:border-slate-700">
                    <div class="flex items-center gap-3 px-3 py-2">
                      <span class="handle-item cursor-grab select-none text-slate-400" title="Arraste para reordenar">::</span>
                      <div class="min-w-0 flex-1">
                        <Text class="block truncate font-medium">{{ item.titulo }}</Text>
                        <Text size="xs" color="muted">
                          {{ item.tipo_label }}
                          <template v-if="item.tipo === 'PDF'"> - {{ item.total_paginas }} pagina(s)</template>
                          - {{ item.obrigatorio ? 'Obrigatorio' : 'Opcional' }}
                        </Text>
                      </div>
                      <a
                        v-if="item.tem_arquivo"
                        :href="route('treinamentos.professor.itens.arquivo', [curso.id, item.id])"
                        target="_blank"
                        rel="noopener"
                        class="text-sm text-sky-600 hover:underline"
                      >Abrir</a>
                      <Button
                        v-if="item.tipo === 'PROVA'"
                        size="sm"
                        variant="outline"
                        :href="route('treinamentos.professor.provas', { treinamento: curso.id, item: item.id })"
                      >Questões</Button>
                      <Button size="sm" variant="outline" @click="abrirItem(pasta, item)">Editar</Button>
                      <Button size="sm" variant="danger" @click="excluirItem(item)">Excluir</Button>
                    </div>

                    <div v-if="item.tipo === 'VIDEO'" class="space-y-2 border-t border-slate-200 px-3 py-2 dark:border-slate-700">
                      <template v-if="item.video">
                        <div class="flex flex-wrap items-center gap-2">
                          <Text class="font-medium">{{ item.video.titulo }}</Text>
                          <Badge :variant="item.video.status === 'FALHOU' ? 'danger' : item.video.status === 'PRONTO' ? 'success' : 'warning'">{{ item.video.status_label }}</Badge>
                          <Text v-if="item.video.duracao_segundos != null" size="xs" color="muted">{{ formatarDuracao(item.video.duracao_segundos) }}</Text>
                        </div>
                        <Text v-if="avisoDoVideo(item.video)" size="xs" color="muted">{{ avisoDoVideo(item.video) }}</Text>
                      </template>
                      <Text v-else size="xs" color="muted">Nenhum vídeo vinculado a este item.</Text>

                      <div class="flex flex-wrap gap-2">
                        <Button size="sm" variant="outline" @click="abrirSeletor(item)">{{ item.video ? 'Trocar vídeo' : 'Escolher vídeo' }}</Button>
                        <Button v-if="item.video" size="sm" variant="outline" @click="desvincularVideo(item)">Desvincular</Button>
                      </div>

                      <UploaderVideo v-if="item.video" :key="item.video.id" :video="item.video" @atualizado="atualizarVideo" />
                    </div>
                  </div>
                </template>
              </draggable>

              <div class="mt-3">
                <Button size="sm" variant="outline" @click="abrirItem(pasta)">+ Adicionar item</Button>
              </div>
            </CardBase>
          </template>
        </draggable>
      </div>
    </div>

    <!-- Modal de pasta -->
    <Modal :show="pastaAberta" max-width="lg" @close="pastaAberta = false">
      <form class="space-y-4 p-6" @submit.prevent="salvarPasta">
        <Heading :level="3" class="text-lg font-semibold">{{ pastaEmEdicao ? 'Editar pasta' : 'Nova pasta' }}</Heading>

        <div>
          <label class="mb-1 block text-sm font-medium">Titulo</label>
          <TextInput v-model="formPasta.titulo" class="w-full" />
          <p v-if="formPasta.errors.titulo" class="mt-1 text-sm text-red-600">{{ formPasta.errors.titulo }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Descricao</label>
          <textarea v-model="formPasta.descricao" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Carga horaria (horas)</label>
          <TextInput v-model="formPasta.carga_horaria" type="number" class="w-full" />
          <p v-if="formPasta.errors.carga_horaria" class="mt-1 text-sm text-red-600">{{ formPasta.errors.carga_horaria }}</p>
        </div>

        <div class="flex justify-end gap-2">
          <Button type="button" variant="outline" @click="pastaAberta = false">Cancelar</Button>
          <Button type="submit" :loading="formPasta.processing">Salvar</Button>
        </div>
      </form>
    </Modal>

    <!-- Modal de item -->
    <Modal :show="itemAberto" max-width="lg" @close="itemAberto = false">
      <form class="space-y-4 p-6" @submit.prevent="salvarItem">
        <Heading :level="3" class="text-lg font-semibold">{{ itemEmEdicao ? 'Editar item' : 'Adicionar item' }}</Heading>
        <p v-if="formItem.errors.item" class="text-sm text-red-600">{{ formItem.errors.item }}</p>

        <div v-if="!itemEmEdicao">
          <label class="mb-1 block text-sm font-medium">Tipo</label>
          <select v-model="formItem.tipo" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800">
            <option value="PDF">Material em PDF</option>
            <option value="PROJETO">Projeto</option>
            <option value="VIDEO">Vídeo</option>
            <option value="PROVA">Prova</option>
          </select>
          <p v-if="formItem.errors.tipo" class="mt-1 text-sm text-red-600">{{ formItem.errors.tipo }}</p>
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium">Titulo</label>
          <TextInput v-model="formItem.titulo" class="w-full" />
          <p v-if="formItem.errors.titulo" class="mt-1 text-sm text-red-600">{{ formItem.errors.titulo }}</p>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Descricao</label>
          <textarea v-model="formItem.descricao" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-600 dark:bg-slate-800" />
        </div>

        <div v-if="!ehVideo && !ehProva">
          <label class="mb-1 block text-sm font-medium">
            {{ ehPdf ? 'Arquivo PDF' : 'Arquivo de apoio (opcional)' }}
          </label>
          <input :key="chaveArquivo" type="file" :accept="ehPdf ? 'application/pdf' : undefined" class="w-full text-sm" @change="escolherArquivo">
          <Text v-if="lendoPdf" size="xs" color="muted">Lendo o PDF...</Text>
          <Text v-else-if="ehPdf && formItem.total_paginas" size="xs" color="muted">{{ formItem.total_paginas }} pagina(s)</Text>
          <Text v-if="itemEmEdicao?.arquivo_nome && !formItem.arquivo" size="xs" color="muted">Atual: {{ itemEmEdicao.arquivo_nome }}</Text>
          <p v-if="formItem.errors.arquivo" class="mt-1 text-sm text-red-600">{{ formItem.errors.arquivo }}</p>
          <p v-if="formItem.errors.total_paginas" class="mt-1 text-sm text-red-600">{{ formItem.errors.total_paginas }}</p>
        </div>

        <Text v-if="ehVideo && !itemEmEdicao" size="xs" color="muted">O vídeo é escolhido na biblioteca depois que o item for criado.</Text>

        <div v-if="ehProva && !itemEmEdicao" class="grid grid-cols-2 gap-4">
          <div>
            <label for="item-nota-minima" class="mb-1 block text-sm font-medium">Nota minima (0 a 100)</label>
            <TextInput id="item-nota-minima" v-model="formItem.nota_minima" type="number" min="0" max="100" class="w-full" />
            <p v-if="formItem.errors.nota_minima" class="mt-1 text-sm text-red-600">{{ formItem.errors.nota_minima }}</p>
          </div>
          <div>
            <label for="item-max-tentativas" class="mb-1 block text-sm font-medium">Maximo de tentativas</label>
            <TextInput id="item-max-tentativas" v-model="formItem.max_tentativas" type="number" min="1" max="20" class="w-full" />
            <p v-if="formItem.errors.max_tentativas" class="mt-1 text-sm text-red-600">{{ formItem.errors.max_tentativas }}</p>
          </div>
        </div>
        <Text v-if="ehProva" size="xs" color="muted">
          A prova e sempre obrigatoria. As questoes e a configuracao ficam na aba Provas{{ itemEmEdicao ? '' : ', depois que o item for criado' }}.
        </Text>

        <label v-else class="flex items-center gap-2 text-sm">
          <input v-model="formItem.obrigatorio" type="checkbox">
          Obrigatorio para concluir a pasta
        </label>

        <div class="flex justify-end gap-2">
          <Button type="button" variant="outline" @click="itemAberto = false">Cancelar</Button>
          <Button type="submit" :loading="formItem.processing" :disabled="lendoPdf">Salvar</Button>
        </div>
      </form>
    </Modal>

    <SeletorVideo
      :show="seletorAberto"
      :treinamento-id="curso.id"
      :item="itemDoSeletor"
      @vinculado="recarregarPastas"
      @fechar="seletorAberto = false"
    />
  </div>
</template>
