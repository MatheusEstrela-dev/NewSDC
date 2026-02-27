<template>
  <div class="w-full">
    <div class="bg-slate-900/60 backdrop-blur-xl border border-slate-700/50 rounded-3xl shadow-2xl overflow-hidden">
      
      <div class="h-1.5 w-full flex">
        <div class="flex-1 bg-blue-900"></div>
        <div class="flex-1 bg-blue-700"></div>
        <div class="flex-1 bg-blue-500"></div>
        <div class="flex-1 bg-sky-400"></div>
        <div class="flex-1 bg-blue-500"></div>
        <div class="flex-1 bg-blue-700"></div>
        <div class="flex-1 bg-blue-900"></div>
      </div>

      <div class="p-8 md:p-12">
        <div class="mb-10 text-center" v-if="showHeader">
          <Heading :level="2" color="white">{{ title }}</Heading>
          <Text color="muted" class="mt-2">{{ subtitle }}</Text>
        </div>

        <DropZone 
          :title="dropZoneTitle"
          :subtitle="dropZoneSubtitle"
          :multiple="multiple"
          :accept="accept"
          @files-selected="addFiles"
        />

        <div v-if="files.length > 0" class="mt-10 space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
          <div class="flex items-center justify-between mb-2">
            <Text size="xs" weight="bold" color="muted" class="uppercase tracking-widest">
              Arquivos Ativos ({{ files.length }})
            </Text>
            <button 
              @click="clearFiles" 
              class="text-xs text-red-400 hover:text-red-300 transition-colors uppercase font-bold"
            >
              Limpar tudo
            </button>
          </div>
          
          <FileUploadItem
            v-for="(file, index) in files"
            :key="index"
            :file="file"
            @remove="removeFile(index)"
          />
        </div>

        <div class="mt-10 pt-8 border-t border-slate-800/50 flex flex-col sm:flex-row items-center justify-between gap-4">
          <div class="flex items-center text-slate-500 text-xs">
            <CheckCircleIcon class="w-4 h-4 mr-2 text-emerald-500" />
            Encriptacao ponta-a-ponta ativa
          </div>
          
          <Button 
            @click="uploadAll"
            :disabled="files.length === 0 || isUploading"
            variant="primary"
            size="lg"
            class="w-full sm:w-auto"
          >
            <template v-if="isUploading">
              <span class="animate-spin mr-2">&#9696;</span>
              Processando...
            </template>
            <template v-else>
              Iniciar Upload
            </template>
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import DropZone from '@/Components/Molecules/Upload/DropZone.vue';
import FileUploadItem from '@/Components/Molecules/Upload/FileUploadItem.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Central de Design',
  },
  subtitle: {
    type: String,
    default: 'Arraste os seus assets para a nuvem segura',
  },
  dropZoneTitle: {
    type: String,
    default: 'Pronto para carregar?',
  },
  dropZoneSubtitle: {
    type: String,
    default: 'Solte os seus ficheiros FIG, PDF ou Imagens aqui (Max. 50MB por ficheiro)',
  },
  showHeader: {
    type: Boolean,
    default: true,
  },
  multiple: {
    type: Boolean,
    default: true,
  },
  accept: {
    type: String,
    default: 'image/*,.pdf,.zip,.fig',
  },
  uploadEndpoint: {
    type: String,
    default: '/api/upload',
  },
});

const emit = defineEmits(['upload-start', 'upload-complete', 'upload-error', 'files-changed']);

const files = ref([]);
const isUploading = ref(false);

const addFiles = (newFiles) => {
  for (const file of newFiles) {
    const fileObj = {
      name: file.name,
      size: file.size,
      type: file.type,
      progress: 0,
      raw: file,
    };
    files.value.push(fileObj);
    simulateProgress(files.value.length - 1);
  }
  emit('files-changed', files.value);
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
      if (files.value[index].progress > 100) {
        files.value[index].progress = 100;
      }
    }
  }, 300);
};

const removeFile = (index) => {
  files.value.splice(index, 1);
  emit('files-changed', files.value);
};

const clearFiles = () => {
  files.value = [];
  emit('files-changed', files.value);
};

const uploadAll = async () => {
  isUploading.value = true;
  emit('upload-start', files.value);

  try {
    await new Promise(resolve => setTimeout(resolve, 2500));
    emit('upload-complete', files.value);
    files.value = [];
  } catch (error) {
    emit('upload-error', error);
  } finally {
    isUploading.value = false;
  }
};

defineExpose({
  files,
  addFiles,
  clearFiles,
  uploadAll,
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #1e293b;
  border-radius: 10px;
}
</style>
