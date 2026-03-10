<script setup>
import { computed } from 'vue'

const props = defineProps({
    files: {
        type: Array,
        default: () => []
    },
    selectedFile: {
        type: String,
        default: ''
    }
})

const emit = defineEmits(['select-file'])

const sortedFiles = computed(() => {
    return [...props.files].sort((a, b) => new Date(b.modified) - new Date(a.modified))
})
</script>

<template>
    <div class="w-72 bg-[#0d1117] border-r border-gray-800/50 flex flex-col h-full overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-800/50 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-sm font-semibold text-gray-200 uppercase tracking-wide">Arquivos de Log</h3>
        </div>
        
        <div class="flex-1 overflow-y-auto py-2 custom-scrollbar">
            <div v-if="files.length === 0" class="px-4 py-8 text-center text-gray-600">
                <p class="text-sm italic">Nenhum log encontrado</p>
            </div>
            
            <button
                v-for="file in sortedFiles"
                :key="file.name"
                @click="emit('select-file', file.name)"
                class="w-full text-left px-4 py-3 flex flex-col hover:bg-gray-800 transition-colors group relative"
                :class="selectedFile === file.name ? 'bg-gray-800/50' : ''"
            >
                <div v-if="selectedFile === file.name" class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                
                <div class="flex justify-between items-start mb-1">
                    <span 
                        class="text-sm font-medium truncate pr-2 font-mono"
                        :class="selectedFile === file.name ? 'text-blue-400' : 'text-gray-300 group-hover:text-white'"
                    >
                        {{ file.name }}
                    </span>
                    <span class="text-[10px] text-gray-500 whitespace-nowrap">{{ file.size_human }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                    <span class="text-[10px] text-gray-600">{{ new Date(file.modified).toLocaleDateString() }}</span>
                    <div v-if="file.type !== 'laravel'" class="px-1.5 py-0.5 rounded text-[8px] bg-gray-800 text-gray-400 uppercase tracking-tighter">
                        {{ file.type }}
                    </div>
                </div>
            </button>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #374151;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #4B5563;
}
</style>
