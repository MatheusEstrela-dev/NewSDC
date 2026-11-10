<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import draggable from '@/lib/vuedraggable-src/vuedraggable.js';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';
import { usePdf } from '@/Composables/treinamento/usePdf';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  curso: { type: Object, required: true },
  pastas: { type: Array, default: () => [] },
});

const { show: toast } = useToast();
const { contarPaginas } = usePdf();

// Copia local: o vuedraggable reordena esta lista; o servidor e a fonte de verdade.
const lista = ref(structuredClone(props.pastas));
watch(() => props.pastas, (novas) => { lista.value = structuredClone(novas); });

const totalItens = computed(() => lista.value.reduce((soma, p) => soma + p.itens.length, 0));

const menu = computed(() => [
  { rotulo: 'Meus cursos', href: route('treinamentos.professor.index'), ativo: false },
  { rotulo: 'Conteudo', href: route('treinamentos.professor.conteudo', props.curso.id), ativo: true },
  { rotulo: 'Previa', href: route('treinamentos.professor.previa', props.curso.id), ativo: false },
]);

function avisarErro(erros) {
  toast(Object.values(erros)[0] || 'Nao foi possivel concluir a acao.', 'error');
  lista.value = structuredClone(props.pastas);
}

// ---- Pastas -------------------------------------------------------------

const pastaAberta = ref(false);
const pastaEmEdicao = ref(null);
const formPasta = useForm({ titulo: '', descricao: '', carga_horaria: 0 });

function abrirPasta(pasta = null) {
  pastaEmEdicao.value = pasta;
  formPasta.reset();
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
const formItem = useForm({
  tipo: 'PDF',
  titulo: '',
  descricao: '',
  obrigatorio: true,
  total_paginas: '',
  arquivo: null,
});

const ehPdf = computed(() => (itemEmEdicao.value?.tipo ?? formItem.tipo) === 'PDF');

function abrirItem(pasta, item = null) {
  pastaDoItem.value = pasta;
  itemEmEdicao.value = item;
  formItem.reset();
  formItem.clearErrors();
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

function publicar() {
  router.post(route('treinamentos.professor.publicar', props.curso.id), {}, {
    preserveScroll: true,
    onError: avisarErro,
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
      <!-- Menu do curso (provisorio) -->
      <nav class="flex gap-2 lg:flex-col" aria-label="Menu do curso">
        <Button
          v-for="entrada in menu"
          :key="entrada.rotulo"
          size="sm"
          :variant="entrada.ativo ? 'primary' : 'outline'"
          :href="entrada.href"
        >
          {{ entrada.rotulo }}
        </Button>
      </nav>

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
                  <div class="flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 dark:border-slate-700">
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
                    <Button size="sm" variant="outline" @click="abrirItem(pasta, item)">Editar</Button>
                    <Button size="sm" variant="danger" @click="excluirItem(item)">Excluir</Button>
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

        <div>
          <label class="mb-1 block text-sm font-medium">
            {{ ehPdf ? 'Arquivo PDF' : 'Arquivo de apoio (opcional)' }}
          </label>
          <input type="file" :accept="ehPdf ? 'application/pdf' : undefined" class="w-full text-sm" @change="escolherArquivo">
          <Text v-if="lendoPdf" size="xs" color="muted">Lendo o PDF...</Text>
          <Text v-else-if="ehPdf && formItem.total_paginas" size="xs" color="muted">{{ formItem.total_paginas }} pagina(s)</Text>
          <Text v-if="itemEmEdicao?.arquivo_nome && !formItem.arquivo" size="xs" color="muted">Atual: {{ itemEmEdicao.arquivo_nome }}</Text>
          <p v-if="formItem.errors.arquivo" class="mt-1 text-sm text-red-600">{{ formItem.errors.arquivo }}</p>
          <p v-if="formItem.errors.total_paginas" class="mt-1 text-sm text-red-600">{{ formItem.errors.total_paginas }}</p>
        </div>

        <label class="flex items-center gap-2 text-sm">
          <input v-model="formItem.obrigatorio" type="checkbox">
          Obrigatorio para concluir a pasta
        </label>

        <div class="flex justify-end gap-2">
          <Button type="button" variant="outline" @click="itemAberto = false">Cancelar</Button>
          <Button type="submit" :loading="formItem.processing" :disabled="lendoPdf">Salvar</Button>
        </div>
      </form>
    </Modal>
  </div>
</template>
