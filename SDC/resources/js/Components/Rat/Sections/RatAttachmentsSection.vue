<template>
  <div class="rat-section-card">
    <!-- Header -->
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <PaperClipIcon class="w-5 h-5" />
      </div>
      <div>
        <h3 class="rat-section-title">Imagens e Arquivos</h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Adicione documentos e imagens relacionados Ã  ocorrÃªncia
        </p>
      </div>
    </div>

    <!-- Content -->
    <div class="rat-section-content">

      <!-- Aviso: sem ratId (RAT ainda nÃ£o salvo) -->
      <div
        v-if="!ratId"
        class="p-4 rounded-lg bg-amber-500/10 border border-amber-500/30 text-sm text-amber-400"
      >
        Salve o RAT primeiro para poder adicionar imagens.
      </div>

      <!-- Ãrea de Upload -->
      <template v-else>
        <div
          @drop.prevent="handleDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @dragenter.prevent="isDragging = true"
          :class="[
            'relative border-2 border-dashed rounded-xl p-8 transition-all duration-200',
            isDragging
              ? 'border-blue-500 bg-blue-500/10'
              : 'border-slate-700 hover:border-slate-600 bg-slate-950/30',
          ]"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            @change="handleFileSelect"
            class="hidden"
            accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt"
          />

          <div class="flex flex-col items-center justify-center text-center">
            <div
              :class="[
                'mb-4 p-4 rounded-full transition-all duration-200',
                isDragging ? 'bg-blue-500/20' : 'bg-slate-800/50',
              ]"
            >
              <UploadIcon
                :class="[
                  'w-8 h-8 transition-colors duration-200',
                  isDragging ? 'text-blue-400' : 'text-slate-400',
                ]"
              />
            </div>

            <h4 :class="['text-sm font-medium mb-2', isDragging ? 'text-blue-400' : 'text-slate-300']">
              {{ isDragging ? 'Solte os arquivos aqui' : 'Arraste e solte arquivos aqui' }}
            </h4>
            <p class="text-xs text-slate-500 mb-4">ou</p>

            <button
              @click="fileInput?.click()"
              type="button"
              :disabled="uploading"
              class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center gap-2"
            >
              <span v-if="uploading" class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></span>
              <PlusIcon v-else class="w-4 h-4" />
              {{ uploading ? 'Enviando...' : 'Selecionar Arquivos' }}
            </button>

            <p class="text-xs text-slate-500 mt-4">
              Formatos: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, GIF â€” mÃ¡x. 10 MB
            </p>
          </div>
        </div>

        <!-- Lista de Imagens/Arquivos -->
        <div v-if="imagens.length > 0" class="mt-6 space-y-3">
          <h4 class="text-sm font-medium text-slate-300 mb-4">
            Arquivos ({{ imagens.length }})
          </h4>

          <div
            v-for="imagem in imagens"
            :key="imagem.id"
            class="flex items-center gap-4 p-4 rounded-lg bg-slate-950/50 border border-slate-700/50 hover:border-slate-600 transition-all duration-200"
          >
            <div class="flex-shrink-0">
              <div class="p-2 rounded-lg bg-slate-800/50">
                <DocumentIcon class="w-5 h-5 text-slate-400" />
              </div>
            </div>

            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-slate-200 truncate">{{ imagem.nome }}</p>
              <p class="text-xs text-slate-500 mt-0.5">
                {{ formatFileSize(imagem.tamanho) }}
                <span v-if="imagem.tipo" class="ml-2">â€¢ {{ imagem.tipo }}</span>
              </p>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
              <a
                v-if="imagem.url"
                :href="imagem.url"
                target="_blank"
                class="p-2 rounded-lg text-slate-400 hover:text-blue-400 hover:bg-blue-500/10 transition-all duration-200"
                title="Baixar arquivo"
              >
                <DownloadIcon class="w-5 h-5" />
              </a>
              <button
                @click="removeImagem(imagem.id)"
                type="button"
                :disabled="deletingId === imagem.id"
                class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 disabled:opacity-50 transition-all duration-200"
                title="Remover arquivo"
              >
                <span v-if="deletingId === imagem.id" class="animate-spin w-5 h-5 border-2 border-red-400 border-t-transparent rounded-full block"></span>
                <TrashIcon v-else class="w-5 h-5" />
              </button>
            </div>
          </div>
        </div>

        <!-- Nenhum arquivo -->
        <div
          v-else
          class="mt-6 p-6 rounded-lg bg-slate-950/30 border border-slate-700/30 text-center"
        >
          <p class="text-sm text-slate-500">
            Nenhum arquivo adicionado ainda. Use a Ã¡rea acima para fazer upload.
          </p>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import DocumentIcon from '../../Icons/DocumentIcon.vue';
import DownloadIcon from '../../Icons/DownloadIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PlusIcon from '../../Icons/PlusIcon.vue';

const props = defineProps({
  ratId: {
    type: String,
    default: null,
  },
  imagens: {
    type: Array,
    default: () => [],
  },
});

const fileInput  = ref(null);
const isDragging = ref(false);
const uploading  = ref(false);
const deletingId = ref(null);

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

const MAX_SIZE = 10 * 1024 * 1024;
const ALLOWED  = [
  'image/jpeg', 'image/png', 'image/gif',
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'text/plain',
];

function validateFile(file) {
  if (file.size > MAX_SIZE) {
    alert(`"${file.name}" excede o tamanho mÃ¡ximo de 10 MB.`);
    return false;
  }
  if (!ALLOWED.includes(file.type)) {
    alert(`Tipo de arquivo nÃ£o permitido: ${file.type}`);
    return false;
  }
  return true;
}

async function uploadFile(file) {
  if (!validateFile(file)) return;

  const formData = new FormData();
  formData.append('file', file);

  uploading.value = true;
  try {
    await axios.post(route('rat.imagens.store', props.ratId), formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    router.reload({ only: ['rat'] });
  } catch (err) {
    alert('Erro ao enviar arquivo: ' + (err.response?.data?.message ?? err.message));
  } finally {
    uploading.value = false;
  }
}

async function handleDrop(event) {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files);
  for (const file of files) {
    await uploadFile(file);
  }
}

async function handleFileSelect(event) {
  const files = Array.from(event.target.files);
  for (const file of files) {
    await uploadFile(file);
  }
  if (fileInput.value) fileInput.value.value = '';
}

async function removeImagem(imagemId) {
  if (!confirm('Remover este arquivo?')) return;

  deletingId.value = imagemId;
  try {
    await axios.delete(route('rat.imagens.destroy', { id: props.ratId, imagemId }));
    router.reload({ only: ['rat'] });
  } catch (err) {
    alert('Erro ao remover arquivo: ' + (err.response?.data?.message ?? err.message));
  } finally {
    deletingId.value = null;
  }
}
</script>

