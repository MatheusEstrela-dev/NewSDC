<template>
  <div 
    class="flex items-center p-4 bg-slate-800/40 rounded-xl border border-slate-700/50 group transition-all duration-300 hover:bg-slate-800/60 hover:border-cyan-500/20"
  >
    <div class="mr-4 p-3 bg-slate-900 rounded-lg">
      <component :is="fileIcon" class="w-5 h-5" :class="iconColorClass" />
    </div>

    <div class="flex-1 min-w-0">
      <div class="flex justify-between items-center mb-2">
        <p class="text-sm font-semibold text-slate-200 truncate pr-4">{{ file.name }}</p>
        <span class="text-[10px] font-mono text-slate-500 bg-slate-900 px-2 py-0.5 rounded">
          {{ formatSize(file.size) }}
        </span>
      </div>
      
      <div class="w-full bg-slate-900 rounded-full h-1 overflow-hidden">
        <div 
          class="h-full rounded-full transition-all duration-700 ease-out bg-gradient-to-r from-blue-600 to-sky-400"
          :style="{ width: file.progress + '%' }"
        ></div>
      </div>
    </div>

    <button 
      @click.stop="$emit('remove')"
      class="ml-4 p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-400/10 transition-all"
    >
      <XMarkIcon class="w-4 h-4" />
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';

const props = defineProps({
  file: {
    type: Object,
    required: true,
  },
});

defineEmits(['remove']);

const fileIcon = computed(() => {
  if (props.file.type?.includes('image')) return DocumentIcon;
  if (props.file.type?.includes('pdf')) return DocumentTextIcon;
  return DocumentIcon;
});

const iconColorClass = computed(() => {
  if (props.file.type?.includes('image')) return 'text-purple-400';
  if (props.file.type?.includes('pdf')) return 'text-red-400';
  return 'text-sky-400';
});

const formatSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};
</script>
