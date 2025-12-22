<template>
  <div class="rat-section-card">
    <!-- Header -->
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

    <!-- Content -->
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

          <p class="text-xs text-slate-500 mb-4">
            ou
          </p>

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
          <p class="text-xs text-slate-600 mt-1">
            Tamanho máximo por arquivo: 10MB
          </p>
        </div>
      </div>

      <!-- Lista de Arquivos Adicionados -->
      <div v-if="localData.anexos && localData.anexos.length > 0" class="mt-6 space-y-3">
        <h4 class="text-sm font-medium text-slate-300 mb-4">
          Arquivos Anexados ({{ localData.anexos.length }})
        </h4>

        <div
          v-for="(anexo, index) in localData.anexos"
          :key="anexo.id || index"
          class="flex items-center gap-4 p-4 rounded-lg bg-slate-950/50 border border-slate-700/50 hover:border-slate-600 transition-all duration-200"
        >
          <!-- Ícone do Tipo de Arquivo -->
          <div class="flex-shrink-0">
            <div class="p-2 rounded-lg bg-slate-800/50">
              <DocumentIcon class="w-5 h-5 text-slate-400" />
            </div>
          </div>

          <!-- Informações do Arquivo -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-200 truncate">
              {{ anexo.nome || anexo.name || 'Arquivo sem nome' }}
            </p>
            <p class="text-xs text-slate-500 mt-0.5">
              {{ formatFileSize(anexo.tamanho || anexo.size) }}
              <span v-if="anexo.tipo || anexo.type" class="ml-2">
                • {{ anexo.tipo || anexo.type }}
              </span>
            </p>
          </div>

          <!-- Ações -->
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
              @click="removeFile(index)"
              type="button"
              class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-all duration-200"
              title="Remover arquivo"
            >
              <TrashIcon class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Mensagem quando não há arquivos -->
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
import { ref, watch, nextTick } from 'vue';
import PaperClipIcon from '../../Icons/PaperClipIcon.vue';
import UploadIcon from '../../Icons/UploadIcon.vue';
import DocumentIcon from '../../Icons/DocumentIcon.vue';
import DownloadIcon from '../../Icons/DownloadIcon.vue';
import TrashIcon from '../../Icons/TrashIcon.vue';
import PlusIcon from '../../Icons/PlusIcon.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue']);

const fileInput = ref(null);
const isDragging = ref(false);

const localData = ref({
  anexos: props.modelValue?.anexos || [],
});

// Formatar tamanho do arquivo
function formatFileSize(bytes) {
  if (!bytes) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

// Validar arquivo
function validateFile(file) {
  const maxSize = 10 * 1024 * 1024; // 10MB
  const allowedTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
  ];

  if (file.size > maxSize) {
    alert(`O arquivo "${file.name}" excede o tamanho máximo de 10MB.`);
    return false;
  }

  if (!allowedTypes.includes(file.type)) {
    alert(`O tipo de arquivo "${file.type}" não é permitido.`);
    return false;
  }

  return true;
}

// Armazenar arquivos fora do estado reativo para melhor performance
const fileMap = new Map();

// Processar arquivos
async function processFiles(files) {
  const fileArray = Array.from(files);
  const validFiles = fileArray.filter(validateFile);

  const newAnexos = [];

  for (const file of validFiles) {
    const anexoId = Date.now() + Math.random();
    
    // Armazenar arquivo no Map (não reativo)
    fileMap.set(anexoId, file);
    
    const anexo = {
      id: anexoId,
      nome: file.name,
      name: file.name,
      tamanho: file.size,
      size: file.size,
      tipo: file.type,
      type: file.type,
      data_upload: new Date().toISOString(),
    };

    // Se for imagem, criar preview de forma assíncrona
    if (file.type.startsWith('image/')) {
      try {
        const preview = await new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = (e) => resolve(e.target.result);
          reader.onerror = reject;
          reader.readAsDataURL(file);
        });
        anexo.preview = preview;
      } catch (error) {
        console.warn('Erro ao criar preview da imagem:', error);
      }
    }

    newAnexos.push(anexo);
  }

  // Adicionar todos os anexos de uma vez
  const updatedAnexos = [...localData.value.anexos, ...newAnexos];
  localData.value.anexos = updatedAnexos;
  
  // Emitir apenas uma vez após todas as atualizações
  nextTick(() => {
    emit('update:modelValue', { anexos: updatedAnexos });
  });
}

// Handle drag and drop
async function handleDrop(event) {
  event.preventDefault();
  event.stopPropagation();
  isDragging.value = false;
  const files = event.dataTransfer.files;
  if (files.length > 0) {
    try {
      await processFiles(files);
    } catch (error) {
      console.error('Erro ao processar arquivos:', error);
      alert('Erro ao processar arquivos. Tente novamente.');
    }
  }
}

// Handle file select
async function handleFileSelect(event) {
  const files = event.target.files;
  if (files.length > 0) {
    try {
      await processFiles(files);
    } catch (error) {
      console.error('Erro ao processar arquivos:', error);
      alert('Erro ao processar arquivos. Tente novamente.');
    }
  }
  // Reset input para permitir selecionar o mesmo arquivo novamente
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

// Remover arquivo
function removeFile(index) {
  const anexo = localData.value.anexos[index];
  if (anexo && anexo.id) {
    fileMap.delete(anexo.id);
  }
  const updatedAnexos = localData.value.anexos.filter((_, i) => i !== index);
  localData.value.anexos = updatedAnexos;
  nextTick(() => {
    emit('update:modelValue', { anexos: updatedAnexos });
  });
}

// Download arquivo
function downloadFile(anexo) {
  if (anexo.url) {
    window.open(anexo.url, '_blank');
  } else if (anexo.id && fileMap.has(anexo.id)) {
    const file = fileMap.get(anexo.id);
    const url = URL.createObjectURL(file);
    const a = document.createElement('a');
    a.href = url;
    a.download = file.name;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }
}

// Watch simplificado - apenas sincronizar quando props mudarem externamente
watch(
  () => props.modelValue?.anexos,
  (newAnexos) => {
    if (newAnexos && Array.isArray(newAnexos)) {
      // Comparar IDs para evitar atualizações desnecessárias
      const currentIds = localData.value.anexos.map(a => a.id).sort().join(',');
      const newIds = newAnexos.map(a => a.id).sort().join(',');
      
      if (currentIds !== newIds) {
        localData.value.anexos = [...newAnexos];
      }
    }
  },
  { deep: false } // Não usar deep watch para melhor performance
);
</script>

