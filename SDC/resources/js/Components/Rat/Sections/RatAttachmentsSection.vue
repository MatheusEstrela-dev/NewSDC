<template>
  <div class="rat-section-card">
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <PaperClipIcon class="w-5 h-5" />
      </div>
      <div>
        <h3 class="rat-section-title">Anexos</h3>
        <p class="text-xs text-slate-500 mt-0.5">
          Adicione documentos, imagens e outros arquivos relacionados à ocorrência
        </p>
      </div>
    </div>

    <div class="rat-section-content">
      <!-- Área de Drag and Drop -->
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

          <h4
            :class="[
              'text-sm font-medium mb-2 transition-colors duration-200',
              isDragging ? 'text-blue-400' : 'text-slate-300',
            ]"
          >
            {{ isDragging ? 'Solte os arquivos aqui' : 'Arraste e solte arquivos aqui' }}
          </h4>

          <p class="text-xs text-slate-500 mb-4">ou</p>

          <button
            @click="fileInput?.click()"
            type="button"
            class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-500 transition-all duration-200 flex items-center gap-2"
          >
            <PlusIcon class="w-4 h-4" />
            Selecionar Arquivos
          </button>

          <p class="text-xs text-slate-500 mt-4">
            Formatos aceitos: PDF, DOC, DOCX, XLS, XLSX, TXT, imagens (JPG, PNG, GIF)
          </p>
          <p class="text-xs text-slate-600 mt-1">Tamanho máximo por arquivo: 10MB</p>
        </div>
      </div>

      <!-- Erro de upload -->
      <p v-if="uploadError" class="mt-3 text-sm text-red-400">{{ uploadError }}</p>

      <!-- Lista de Arquivos -->
      <div v-if="anexos && anexos.length > 0" class="mt-6 space-y-3">
        <h4 class="text-sm font-medium text-slate-300 mb-4">
          Arquivos Anexados ({{ anexos.length }})
        </h4>

        <div
          v-for="(anexo, index) in anexos"
          :key="anexo.id || index"
          class="flex items-center gap-4 p-4 rounded-lg bg-slate-950/50 border border-slate-700/50 hover:border-slate-600 transition-all duration-200"
        >
          <div class="flex-shrink-0">
            <div class="p-2 rounded-lg bg-slate-800/50">
              <DocumentIcon class="w-5 h-5 text-slate-400" />
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-200 truncate">
              {{ anexo.nome_original || anexo.nome || anexo.name || 'Arquivo sem nome' }}
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ formatFileSize(anexo.tamanho_bytes || anexo.tamanho || anexo.size) }}
              <span v-if="anexo.mime_type || anexo.tipo || anexo.type" class="ml-2">
                • {{ anexo.mime_type || anexo.tipo || anexo.type }}
              </span>
              <span v-if="anexo.status === 'pending'" class="ml-2 text-amber-400">• pendente de envio</span>
            </p>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0">
            <button
              v-if="anexo.url"
              @click="downloadFile(anexo)"
              type="button"
              class="p-2 rounded-lg text-slate-400 hover:text-blue-400 hover:bg-blue-500/10 transition-all duration-200"
              title="Baixar arquivo"
            >
              <DownloadIcon class="w-5 h-5" />
            </button>
            <button
              @click="$emit('remove', anexo.id)"
              type="button"
              class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all duration-200"
              title="Remover arquivo"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <div
        v-else
        class="mt-6 p-6 rounded-lg bg-slate-950/30 border border-slate-700/30 text-center"
      >
        <p class="text-sm text-slate-500">
          Nenhum arquivo anexado ainda. Adicione arquivos usando a área acima.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { formatFileSize } from '@/utils/fileTypes';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import DocumentIcon from '../../Icons/DocumentIcon.vue';
import DownloadIcon from '../../Icons/DownloadIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PlusIcon from '../../Icons/PlusIcon.vue';

defineProps({
  anexos: {
    type: Array,
    default: () => [],
  },
  uploadError: {
    type: String,
    default: null,
  },
});

const emit = defineEmits(['upload', 'remove']);

const fileInput = ref(null);
const isDragging = ref(false);

function handleDrop(event) {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files);
  if (files.length > 0) emit('upload', files);
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files);
  if (files.length > 0) emit('upload', files);
  if (fileInput.value) fileInput.value.value = '';
}

function downloadFile(anexo) {
  window.open(anexo.url, '_blank');
}
</script>
