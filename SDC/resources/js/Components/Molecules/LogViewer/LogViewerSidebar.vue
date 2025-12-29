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
    <div class="w-64 bg-gray-900 border-r border-gray-800 flex flex-col h-full overflow-hidden">
        <div class="p-4 border-b border-gray-800">
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Arquivos de Log</h3>
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
