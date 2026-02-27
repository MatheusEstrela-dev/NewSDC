<template>
  <div 
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="handleDrop"
    @click="triggerFileInput"
    :class="[
      'relative border-2 border-dashed rounded-2xl p-12 flex flex-col items-center justify-center transition-all duration-300 group cursor-pointer',
      isDragging 
        ? 'border-cyan-400 bg-cyan-500/10 scale-[1.01] shadow-[0_0_30px_rgba(34,211,238,0.2)]' 
        : 'border-slate-700 hover:border-slate-500 hover:bg-slate-800/30'
    ]"
  >
    <input 
      ref="fileInputRef"
      type="file" 
      class="hidden" 
      :multiple="multiple"
      :accept="accept"
      @change="handleFileSelect"
    />
    
    <div 
      :class="[
        'p-5 rounded-2xl mb-6 transition-all duration-500',
        isDragging 
          ? 'bg-cyan-500 shadow-lg shadow-cyan-500/50 rotate-12' 
          : 'bg-slate-800 group-hover:bg-slate-700'
      ]"
    >
      <UploadIcon :class="['w-10 h-10', isDragging ? 'text-white' : 'text-cyan-400']" />
    </div>
    
    <p class="text-xl font-semibold text-slate-200">{{ title }}</p>
    <p class="text-sm text-slate-500 mt-2 text-center max-w-xs">{{ subtitle }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import UploadIcon from '@/Components/Icons/UploadIcon.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Arraste seus arquivos',
  },
  subtitle: {
    type: String,
    default: 'Solte os arquivos FIG, PDF ou Imagens aqui (Max. 50MB)',
  },
  multiple: {
    type: Boolean,
    default: true,
  },
  accept: {
    type: String,
    default: 'image/*,.pdf,.zip,.fig',
  },
});

const emit = defineEmits(['files-selected']);

const isDragging = ref(false);
const fileInputRef = ref(null);

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  emit('files-selected', files);
  event.target.value = '';
};

const handleDrop = (event) => {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files);
  emit('files-selected', files);
};

defineExpose({
  triggerFileInput,
});
</script>
