<template>
  <div
    class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 overflow-hidden flex flex-col h-full max-h-[500px]"
  >
    <div class="px-4 sm:px-6 py-3 sm:py-4 border-b border-slate-700 bg-slate-800/50">
      <h3 class="text-base sm:text-lg font-semibold text-white flex items-center gap-2">
        <span class="w-1 h-6 bg-blue-500 rounded-full flex-shrink-0"></span>
        <span>3. Documentos Anexados</span>
      </h3>
    </div>

    <div class="p-4 sm:p-6 flex-grow flex flex-col space-y-3 sm:space-y-4">
      <!-- Upload Area -->
      <div
        class="border-2 border-dashed border-slate-600 rounded-lg p-4 sm:p-6 flex flex-col items-center justify-center text-center hover:border-blue-500 hover:bg-slate-700/30 transition-all duration-200 cursor-pointer group"
        @click="triggerFileInput"
        @drop.prevent="handleDrop"
        @dragover.prevent
      >
        <input
          ref="fileInput"
          type="file"
          multiple
          accept=".pdf,.kml,.doc,.docx"
          class="hidden"
          @change="handleFileSelect"
        />
        <div
          class="p-2 sm:p-3 rounded-full bg-slate-700 group-hover:bg-blue-500/20 group-hover:text-blue-400 transition-colors mb-2"
        >
          <UploadIcon class="w-5 h-5 sm:w-6 sm:h-6" />
        </div>
        <p class="text-xs sm:text-sm text-slate-300 font-medium">Clique ou arraste arquivos</p>
        <p class="text-xs text-slate-500 mt-1">PDF, KML, DOCX (Máx. 50MB)</p>
      </div>

      <!-- Lista de Documentos -->
      <ul class="space-y-2 overflow-y-auto pr-1 sm:pr-2 custom-scrollbar flex-grow">
        <li
          v-for="doc in documents"
          :key="doc.id"
          class="flex items-center justify-between bg-slate-900/50 hover:bg-slate-700/50 p-2 sm:p-3 rounded-lg border border-slate-700/50 transition-colors group"
        >
          <div class="flex items-center gap-2 sm:gap-3 overflow-hidden min-w-0 flex-1">
            <div :class="['p-1.5 sm:p-2 rounded bg-slate-800 flex-shrink-0', getFileTypeInfo(doc.nome || doc.name || '').corBg]">
              <component :is="getFileIcon(doc.nome || doc.name || '')" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
            </div>
            <span
              class="text-xs sm:text-sm font-medium text-slate-300 truncate group-hover:text-white transition-colors min-w-0"
              :title="doc.nome || doc.name || 'Documento'"
            >
              {{ doc.nome || doc.name || 'Documento' }}
            </span>
          </div>
          <button
            class="text-slate-500 hover:text-blue-400 p-1 rounded hover:bg-slate-700 transition-all flex-shrink-0 ml-2"
            @click="$emit('remove', doc.id)"
          >
            <DownloadIcon class="w-4 h-4" />
          </button>
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { getFileTypeInfo } from '../../utils/fileTypes';
import UploadIcon from '../Icons/UploadIcon.vue';
import DownloadIcon from '../Icons/DownloadIcon.vue';
import DocumentTextIcon from '../Icons/DocumentTextIcon.vue';
import MapIcon from '../Icons/MapIcon.vue';

defineProps({
  documents: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['upload', 'remove']);

const fileInput = ref(null);

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileSelect(event) {
  const files = Array.from(event.target.files);
  emit('upload', files);
}

function handleDrop(event) {
  const files = Array.from(event.dataTransfer.files);
  emit('upload', files);
}

function getFileIcon(filename) {
  const info = getFileTypeInfo(filename);
  const iconMap = {
    pdf: DocumentTextIcon,
    kml: MapIcon,
    doc: DocumentTextIcon,
  };
  return iconMap[info.tipo] || DocumentTextIcon;
}
</script>

