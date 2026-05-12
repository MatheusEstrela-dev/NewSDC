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
        <p class="text-xs text-slate-500 mt-0.5">Gestão de arquivos e fotos da ocorrência</p>
      </div>
    </div>

    <div class="rat-section-content">
      <!-- Upload area -->
      <div v-if="!props.viewOnly" class="mb-8">
        <div
          @drop.prevent="handleDrop"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @click="fileInput?.click()"
          :class="[
            'border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-all',
            isDragging ? 'border-blue-500 bg-blue-500/10' : 'border-slate-700 bg-slate-950/30 hover:border-slate-500'
          ]"
        >
          <input ref="fileInput" type="file" multiple class="hidden" :accept="acceptedTypes" @change="handleFileSelect" />
          <div class="flex flex-col items-center">
            <svg class="w-10 h-10 text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
            <p class="text-sm font-medium text-slate-300">Arraste arquivos ou clique para selecionar</p>
            <p class="text-xs text-slate-500 mt-1">PDF, DOC, DOCX, XLS, XLSX, imagens (Max 20MB)</p>
          </div>
        </div>
      </div>

      <!-- Errors/Loading -->
      <div v-if="uploadError" class="mb-4 text-xs text-red-500">{{ uploadError }}</div>
      <div v-if="uploading" class="mb-4 text-xs text-blue-400 flex items-center gap-2">
        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg>
        Enviando arquivos...
      </div>

      <!-- List -->
      <div v-if="localData.anexos && localData.anexos.length > 0" class="space-y-3">
        <div v-for="(anexo, index) in localData.anexos" :key="anexo.id || index" class="p-4 rounded-lg bg-slate-950/50 border border-slate-700/50 flex items-center justify-between">
          <div class="flex items-center gap-3 overflow-hidden">
            <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            <div class="truncate">
              <div class="text-sm font-medium text-slate-200 truncate">{{ anexo.nome_original || anexo.descricao || anexo.nome || anexo.name }}</div>
              <div class="text-[10px] text-slate-500 uppercase">{{ anexo.mime_type || anexo.categoria || anexo.tipo || anexo.type }}</div>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <button v-if="anexo.url" @click="downloadFile(anexo)" class="p-2 text-blue-400 hover:bg-blue-500/10 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg></button>
            <button v-if="!props.viewOnly" @click="removeFile(index)" class="p-2 text-red-500 hover:bg-red-500/10 rounded-lg"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
          </div>
        </div>
      </div>
      <div v-else class="text-center py-8 text-slate-500 text-sm">Nenhum anexo encontrado.</div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue:    { type: Object, default: () => ({ anexos: [] }) },
  uploading:     { type: Boolean, default: false },
  uploadError:   { type: String, default: null },
  viewOnly:      { type: Boolean, default: false },
  acceptedTypes: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue', 'upload-file', 'remove-file']);

const fileInput = ref(null);
const isDragging = ref(false);
const localData = ref({
  anexos: props.modelValue?.anexos || [],
});

const handleDrop = (e) => {
  isDragging.value = false;
  const files = e.dataTransfer.files;
  if (files.length > 0) processFiles(files);
};

const handleFileSelect = (e) => {
  const files = e.target.files;
  if (files.length > 0) processFiles(files);
};

const processFiles = (files) => {
  for (const file of Array.from(files)) {
    const tempId = Date.now() + Math.random();
    emit('upload-file', { file, tempId });
  }
};

const removeFile = (index) => {
  const anexo = localData.value.anexos[index];
  if (anexo?.id) emit('remove-file', anexo.id);
};

const downloadFile = (anexo) => {
  if (anexo.url) window.open(anexo.url, '_blank');
};

watch(() => props.modelValue?.anexos, (nv) => {
  if (nv) localData.value.anexos = [...nv];
}, { deep: true });
</script>
