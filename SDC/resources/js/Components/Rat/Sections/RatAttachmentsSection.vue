<template>
  <div class="rat-section-card">
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Anexos e Documentos</h3>
        <p class="text-xs text-slate-500 mt-0.5">Arquivos são enviados ao servidor imediatamente ao serem selecionados</p>
      </div>
    </div>

    <div class="rat-section-content">
      <!-- Upload area -->
      <div v-if="!props.viewOnly" class="mb-6">
        <div
          @drop.prevent="handleDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @click="triggerFileInput"
          :class="[
            'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all select-none',
            isDragging
              ? 'border-blue-500 bg-blue-500/10'
              : uploading
                ? 'border-slate-600 bg-slate-950/10 cursor-not-allowed opacity-60'
                : 'border-slate-700 bg-slate-950/30 hover:border-slate-500 hover:bg-slate-950/50'
          ]"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            class="hidden"
            :accept="acceptedTypes"
            :disabled="uploading"
            @change="handleFileSelect"
          />
          <div class="flex flex-col items-center gap-2">
            <svg v-if="uploading" class="w-8 h-8 text-blue-400 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
            </svg>
            <svg v-else class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
            </svg>
            <p class="text-sm font-medium text-slate-300">
              {{ uploading ? 'Enviando arquivo...' : 'Arraste arquivos ou clique para selecionar' }}
            </p>
            <p class="text-xs text-slate-500">
              PDF, DOC, DOCX, XLS, XLSX, imagens — máximo <strong class="text-slate-400">20 MB</strong> por arquivo
            </p>
          </div>
        </div>

        <!-- Erros de tamanho (validação frontend) -->
        <div v-if="sizeErrors.length > 0" class="mt-3 space-y-1.5">
          <div
            v-for="(err, i) in sizeErrors"
            :key="i"
            class="flex items-start gap-2 px-3 py-2 rounded-lg bg-red-500/10 border border-red-500/30 text-xs text-red-400"
          >
            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ err }}
          </div>
        </div>

        <!-- Erro de upload (vindo do servidor) -->
        <div v-if="uploadError" class="mt-3 flex items-start gap-2 px-3 py-2 rounded-lg bg-red-500/10 border border-red-500/30 text-xs text-red-400">
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ uploadError }}
        </div>
      </div>

      <!-- Lista de arquivos -->
      <div v-if="localData.anexos && localData.anexos.length > 0" class="space-y-2">
        <div
          v-for="(anexo, index) in localData.anexos"
          :key="anexo.id || index"
          class="flex items-center justify-between gap-3 p-3.5 rounded-lg bg-slate-950/50 border border-slate-700/50"
        >
          <!-- Ícone + infos -->
          <div class="flex items-center gap-3 overflow-hidden flex-1 min-w-0">
            <div class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
              <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
            </div>
            <div class="overflow-hidden">
              <div class="text-sm font-medium text-slate-200 truncate">
                {{ anexo.nome_original || anexo.descricao || anexo.nome || anexo.name }}
              </div>
              <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                <span class="text-[10px] font-mono text-slate-500 uppercase">
                  {{ formatMime(anexo.mime_type) || anexo.categoria || anexo.tipo || anexo.type }}
                </span>
                <span v-if="anexo.tamanho_bytes" class="text-[10px] text-slate-600">
                  {{ formatSize(anexo.tamanho_bytes) }}
                </span>
                <!-- Endereço de indexação -->
                <a
                  v-if="anexo.url"
                  :href="anexo.url"
                  target="_blank"
                  class="text-[10px] text-blue-500 hover:text-blue-400 font-mono truncate max-w-[200px]"
                  :title="anexo.url"
                  @click.stop
                >
                  {{ truncateUrl(anexo.url) }}
                </a>
              </div>
            </div>
          </div>

          <!-- Ações -->
          <div class="flex items-center gap-1 flex-shrink-0">
            <a
              v-if="anexo.url"
              :href="anexo.url"
              target="_blank"
              download
              class="p-1.5 text-blue-400 hover:bg-blue-500/10 rounded-lg transition-colors"
              title="Baixar arquivo"
              @click.stop
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
              </svg>
            </a>
            <button
              v-if="!props.viewOnly"
              type="button"
              @click.stop="removeFile(index)"
              class="p-1.5 text-red-500 hover:bg-red-500/10 rounded-lg transition-colors"
              title="Remover arquivo"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-8 text-slate-500 text-sm">Nenhum anexo encontrado.</div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const MAX_SIZE_BYTES = 20 * 1024 * 1024; // 20 MB

const props = defineProps({
  modelValue:    { type: Object,  default: () => ({ anexos: [] }) },
  uploading:     { type: Boolean, default: false },
  uploadError:   { type: String,  default: null },
  viewOnly:      { type: Boolean, default: false },
  acceptedTypes: { type: String,  default: '' },
});

const emit = defineEmits(['update:modelValue', 'upload-file', 'remove-file']);

const fileInput  = ref(null);
const isDragging = ref(false);
const sizeErrors = ref([]);

const localData = ref({ anexos: props.modelValue?.anexos || [] });

watch(() => props.modelValue?.anexos, (nv) => {
  if (nv) localData.value.anexos = [...nv];
}, { deep: true });

function triggerFileInput() {
  if (props.uploading) return;
  // Reset para permitir selecionar o mesmo arquivo novamente
  if (fileInput.value) fileInput.value.value = '';
  fileInput.value?.click();
}

const handleDrop = (e) => {
  isDragging.value = false;
  if (props.uploading) return;
  processFiles(e.dataTransfer.files);
};

const handleFileSelect = (e) => {
  processFiles(e.target.files);
};

const processFiles = (files) => {
  sizeErrors.value = [];

  for (const file of Array.from(files)) {
    if (file.size > MAX_SIZE_BYTES) {
      const sizeMb = (file.size / 1024 / 1024).toFixed(1);
      sizeErrors.value.push(`"${file.name}" excede o limite de 20 MB (${sizeMb} MB). Reduza o arquivo antes de enviar.`);
      continue;
    }
    const tempId = Date.now() + Math.random();
    emit('upload-file', { file, tempId });
  }
};

const removeFile = (index) => {
  const anexo = localData.value.anexos[index];
  if (anexo?.id) emit('remove-file', anexo.id);
};

function formatSize(bytes) {
  if (!bytes) return '';
  if (bytes >= 1_048_576) return (bytes / 1_048_576).toFixed(1) + ' MB';
  if (bytes >= 1_024)     return (bytes / 1_024).toFixed(0) + ' KB';
  return bytes + ' B';
}

function formatMime(mime) {
  if (!mime) return '';
  const map = {
    'application/pdf':                                                                  'PDF',
    'application/msword':                                                               'DOC',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document':         'DOCX',
    'application/vnd.ms-excel':                                                        'XLS',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':               'XLSX',
    'text/plain':                                                                       'TXT',
    'text/csv':                                                                         'CSV',
  };
  if (map[mime]) return map[mime];
  if (mime.startsWith('image/')) return mime.replace('image/', '').toUpperCase();
  return mime.split('/').pop().toUpperCase();
}

function truncateUrl(url) {
  if (!url) return '';
  try {
    const u = new URL(url);
    const path = u.pathname;
    return path.length > 40 ? '...' + path.slice(-38) : path;
  } catch {
    return url.length > 40 ? '...' + url.slice(-38) : url;
  }
}
</script>
