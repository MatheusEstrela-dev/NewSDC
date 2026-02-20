<script setup>
import { ref, watch, nextTick } from 'vue';
import { XMarkIcon, CloudArrowUpIcon, DocumentIcon, PhotoIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  show: Boolean,
  moduleName: {
    type: String,
    default: 'Arquivos'
  },
  acceptedTypes: {
    type: String,
    default: 'image/*,.pdf,.zip,.fig'
  },
  maxFileSize: {
    type: Number,
    default: 50 // MB
  }
});

const emit = defineEmits(['close', 'upload']);

const files = ref([]);
const isDragging = ref(false);
const isUploading = ref(false);
const fileInput = ref(null);

watch(() => props.show, (newVal) => {
  if (newVal) {
    files.value = [];
    isDragging.value = false;
    isUploading.value = false;
  }
});

const close = () => {
  emit('close');
};

const handleFileSelect = (event) => {
  addFiles(event.target.files);
};

const handleDrop = (event) => {
  isDragging.value = false;
  addFiles(event.dataTransfer.files);
};

const addFiles = (newFiles) => {
  for (let file of newFiles) {
    if (file.size > props.maxFileSize * 1024 * 1024) {
      continue;
    }
    const fileObj = {
      name: file.name,
      size: file.size,
      type: file.type,
      progress: 0,
      raw: file
    };
    files.value.push(fileObj);
    simulateProgress(files.value.length - 1);
  }
};

const simulateProgress = (index) => {
  const interval = setInterval(() => {
    if (!files.value[index]) {
      clearInterval(interval);
      return;
    }
    if (files.value[index].progress >= 100) {
      clearInterval(interval);
    } else {
      files.value[index].progress += Math.floor(Math.random() * 15) + 5;
      if (files.value[index].progress > 100) files.value[index].progress = 100;
    }
  }, 300);
};

const removeFile = (index) => {
  files.value.splice(index, 1);
};

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const clearAll = () => {
  files.value = [];
};

const handleUpload = async () => {
  if (files.value.length === 0) return;

  isUploading.value = true;

  const formData = new FormData();
  files.value.forEach((file, index) => {
    formData.append(`files[${index}]`, file.raw);
  });

  emit('upload', { files: files.value.map(f => f.raw), formData });

  setTimeout(() => {
    isUploading.value = false;
    files.value = [];
    close();
  }, 2000);
};

const openFileDialog = () => {
  fileInput.value?.click();
};
</script>

<template>
  <Transition
    enter-active-class="transition ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-[70] flex items-center justify-center p-4">
      <div
        class="fixed inset-0 bg-slate-950/80 backdrop-blur-md"
        @click="close"
      ></div>

      <Transition
        enter-active-class="transition ease-out duration-300 delay-100"
        enter-from-class="opacity-0 scale-95 translate-y-4"
        enter-to-class="opacity-100 scale-100 translate-y-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100 scale-100 translate-y-0"
        leave-to-class="opacity-0 scale-95 translate-y-4"
      >
        <div
          v-if="show"
          class="relative w-full max-w-xl overflow-hidden rounded-[2rem] shadow-2xl"
          style="background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(24px); border: 1px solid rgba(255, 255, 255, 0.08);"
        >
          <div class="h-1.5 flex w-full">
            <div class="h-full flex-1 bg-slate-900"></div>
            <div class="h-full flex-1 bg-blue-800"></div>
            <div class="h-full flex-1 bg-blue-600"></div>
            <div class="h-full flex-1 bg-cyan-400"></div>
            <div class="h-full flex-1 bg-blue-600"></div>
            <div class="h-full flex-1 bg-blue-800"></div>
            <div class="h-full flex-1 bg-slate-900"></div>
          </div>

          <div class="p-8">
            <header class="mb-8 flex justify-between items-start">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-2 h-2 rounded-full bg-cyan-400 shadow-[0_0_8px_#22d3ee] animate-pulse"></span>
                  <span class="text-cyan-400 text-[10px] font-black uppercase tracking-[0.3em]">Sincronizacao Ativa</span>
                </div>
                <h1 class="text-2xl font-bold text-white tracking-tight">Central de Upload</h1>
                <p class="text-slate-400 text-sm mt-1">{{ moduleName }}</p>
              </div>
              <button
                @click="close"
                class="p-2.5 bg-white/5 rounded-xl border border-white/5 text-slate-400 hover:text-white hover:bg-white/10 transition-all"
              >
                <XMarkIcon class="w-5 h-5" />
              </button>
            </header>

            <div
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              @click="openFileDialog"
              class="relative border-2 border-dashed rounded-2xl p-10 flex flex-col items-center justify-center cursor-pointer transition-all duration-500 group overflow-hidden"
              :class="isDragging
                ? 'border-cyan-400 bg-cyan-500/10 shadow-[0_0_30px_rgba(34,211,238,0.2)] scale-[1.01]'
                : 'border-slate-700 hover:border-slate-500 hover:bg-slate-800/30'"
            >
              <input
                type="file"
                ref="fileInput"
                class="hidden"
                multiple
                :accept="acceptedTypes"
                @change="handleFileSelect"
              >

              <div class="absolute inset-0 bg-gradient-to-br from-cyan-500/0 to-blue-500/0 group-hover:from-cyan-500/5 group-hover:to-blue-500/5 transition-all duration-500"></div>

              <div class="relative mb-6">
                <div
                  class="absolute inset-0 blur-2xl rounded-full scale-150 transition-all duration-700"
                  :class="isDragging ? 'bg-cyan-500/40' : 'bg-cyan-500/10 group-hover:bg-cyan-500/20'"
                ></div>
                <div
                  class="relative p-4 rounded-2xl border transition-all duration-300"
                  :class="isDragging
                    ? 'bg-cyan-500 border-cyan-400 rotate-12 shadow-lg shadow-cyan-500/50'
                    : 'bg-slate-800 border-slate-700 group-hover:border-cyan-500/50'"
                >
                  <CloudArrowUpIcon
                    class="w-10 h-10 transition-all duration-500"
                    :class="isDragging ? 'text-white scale-110' : 'text-cyan-400'"
                  />
                </div>
              </div>

              <p class="text-lg font-semibold text-slate-200 relative">Arraste seus arquivos</p>
              <p class="text-sm text-slate-500 mt-2 text-center relative">
                Os pontilhados acendem ao detectar seus arquivos
                <br>
                <span class="text-slate-600 text-xs">Suporta imagens, PDF e ZIP (Max. {{ maxFileSize }}MB)</span>
              </p>
            </div>

            <div v-if="files.length > 0" class="mt-8 space-y-3 max-h-[280px] overflow-y-auto pr-2 custom-scroll">
              <div class="flex items-center justify-between mb-2 px-1">
                <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                  Fila de Upload ({{ files.length }})
                </span>
                <button
                  @click="clearAll"
                  class="text-[10px] font-bold text-red-400/70 hover:text-red-400 transition-colors uppercase tracking-widest"
                >
                  Limpar tudo
                </button>
              </div>

              <TransitionGroup
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-x-4"
              >
                <div
                  v-for="(file, index) in files"
                  :key="file.name + index"
                  class="flex items-center p-4 rounded-xl border border-white/5 bg-white/[0.02] hover:bg-white/[0.04] hover:border-cyan-500/20 transition-all group/card"
                >
                  <div class="w-11 h-11 rounded-lg bg-slate-900/80 flex items-center justify-center mr-4 border border-white/5 group-hover/card:border-cyan-500/30 transition-colors">
                    <PhotoIcon v-if="file.type.includes('image')" class="w-5 h-5 text-purple-400" />
                    <DocumentIcon v-else-if="file.type.includes('pdf')" class="w-5 h-5 text-red-400" />
                    <DocumentIcon v-else class="w-5 h-5 text-sky-400" />
                  </div>

                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-center mb-2">
                      <p class="text-sm font-semibold text-slate-200 truncate pr-4">{{ file.name }}</p>
                      <span class="text-[10px] font-mono text-slate-500 bg-slate-900 px-2 py-0.5 rounded shrink-0">
                        {{ formatSize(file.size) }}
                      </span>
                    </div>

                    <div class="w-full bg-black/40 h-1.5 rounded-full overflow-hidden p-[1px]">
                      <div
                        class="h-full rounded-full transition-all duration-700 ease-out"
                        :class="file.progress >= 100
                          ? 'bg-gradient-to-r from-emerald-600 to-emerald-400'
                          : 'bg-gradient-to-r from-blue-700 via-cyan-500 to-white shadow-[0_0_10px_rgba(6,182,212,0.5)]'"
                        :style="{ width: file.progress + '%' }"
                      ></div>
                    </div>
                  </div>

                  <button
                    @click.stop="removeFile(index)"
                    class="ml-4 p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-all"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </TransitionGroup>
            </div>

            <footer class="mt-8 pt-6 border-t border-slate-800/50 flex items-center gap-4">
              <div class="hidden sm:flex items-center text-slate-500 text-xs">
                <svg class="w-4 h-4 mr-2 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Criptografia SSL 256-bit
              </div>

              <button
                @click="handleUpload"
                :disabled="files.length === 0 || isUploading"
                class="flex-1 py-4 rounded-xl font-black text-xs tracking-[0.15em] uppercase flex items-center justify-center gap-2 transition-all"
                :class="files.length > 0 && !isUploading
                  ? 'bg-gradient-to-r from-blue-600 to-cyan-500 text-white shadow-[0_0_25px_rgba(6,182,212,0.4)] hover:shadow-[0_0_40px_rgba(6,182,212,0.6)] hover:-translate-y-0.5 active:translate-y-0'
                  : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
              >
                <svg v-if="isUploading" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                </svg>
                {{ isUploading ? 'Sincronizando...' : 'Finalizar Upload' }}
              </button>
            </footer>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<style scoped>
.custom-scroll::-webkit-scrollbar {
  width: 4px;
}
.custom-scroll::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
}
.custom-scroll:hover::-webkit-scrollbar-thumb {
  background: rgba(34, 211, 238, 0.2);
}
</style>
