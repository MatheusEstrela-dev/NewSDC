<script setup>
import { computed } from 'vue'

const props = defineProps({
    logs: {
        type: Array,
        default: () => []
    },
    loading: Boolean,
    filters: Object
})

const emit = defineEmits(['select-log'])

const getLevelColor = (level) => {
    const colors = {
        debug: 'text-gray-400',
        info: 'text-blue-400',
        notice: 'text-cyan-400',
        warning: 'text-yellow-400',
        error: 'text-red-400',
        critical: 'text-red-500 font-bold',
        alert: 'text-red-600 font-bold',
        emergency: 'text-red-700 font-bold underline'
    }
    return colors[level.toLowerCase()] || 'text-gray-300'
}

const getLevelIcon = (level) => {
    const icons = {
        error: 'exclamation-circle',
        critical: 'fire',
        alert: 'bell',
        emergency: 'life-buoy',
        warning: 'exclamation-triangle',
        info: 'info-circle',
        debug: 'bug'
    }
    return icons[level.toLowerCase()] || 'info-circle'
}

const formatDate = (dateStr) => {
    const date = new Date(dateStr)
    return date.toLocaleTimeString('pt-BR', { hour12: false })
}

const formatFullDate = (dateStr) => {
    return new Date(dateStr).toLocaleString('pt-BR')
}
</script>

<template>
    <div class="flex-1 overflow-hidden flex flex-col bg-[#0b0e14]">
        <!-- Empty State -->
        <div v-if="!loading && logs.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-600 p-8 text-center">
            <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1.1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-500">No logs found</h3>
            <p class="text-sm max-w-xs mt-1">Try adjusting your filters or selecting a different log file.</p>
        </div>

        <!-- Table -->
        <div v-else class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-full border-collapse text-xs font-mono">
                <thead class="sticky top-0 bg-[#0b0e14] z-10 border-b border-gray-800 shadow-sm">
                    <tr class="text-left text-gray-500 uppercase tracking-tighter h-8">
                        <th class="px-4 font-semibold w-20">Level</th>
                        <th class="px-2 font-semibold w-24">Time</th>
                        <th class="px-2 font-semibold w-20">Env</th>
                        <th class="px-2 font-semibold">Description</th>
                        <th class="px-4 font-semibold text-right w-16">#</th>
                    </tr>
                </thead>
                <tbody>
                    <tr 
                        v-for="(log, index) in logs" 
                        :key="index"
                        @click="emit('select-log', log)"
                        class="border-b border-gray-800/30 hover:bg-gray-800/20 cursor-pointer group transition-colors h-9"
                    >
                        <td class="px-4 whitespace-nowrap">
                            <span :class="getLevelColor(log.level)" class="flex items-center gap-1.5 uppercase font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="'bg-' + getLevelColor(log.level).split('-')[1] + '-500'"></span>
                                {{ log.level }}
                            </span>
                        </td>
                        <td class="px-2 text-gray-500 whitespace-nowrap">
                            {{ formatDate(log.timestamp) }}
                        </td>
                        <td class="px-2">
                            <span class="px-1.5 py-0.5 rounded bg-gray-800 text-gray-500 text-[10px]">{{ log.context?.environment || 'prod' }}</span>
                        </td>
                        <td class="px-2 truncate max-w-0 text-gray-300 group-hover:text-white transition-colors">
                            {{ log.message }}
                        </td>
                        <td class="px-4 text-right text-gray-600 group-hover:text-gray-400">
                            {{ log.line || index + 1 }}
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Loading Overlay -->
            <div v-if="loading" class="absolute inset-0 bg-[#0b0e14]/50 backdrop-blur-[1px] flex items-center justify-center z-20">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="mt-4 text-xs font-medium text-blue-500 tracking-widest uppercase">Reading Logs...</span>
                </div>
            </div>
        </div>
        
        <!-- Footer Info -->
        <div class="px-6 py-2 bg-gray-900 border-t border-gray-800 flex justify-between items-center text-[10px] text-gray-600 shrink-0">
            <div class="flex gap-4">
                <span>Memory: <span class="text-gray-400 font-mono">1.25 MB</span></span>
                <span>Duration: <span class="text-gray-400 font-mono">15ms</span></span>
            </div>
            <div class="flex gap-4 items-center">
                <span>Version: <span class="text-gray-400 font-mono">v2.1.0</span></span>
                <span class="hidden sm:inline italic">SDC Unified Logging System</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #0b0e14;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #1f2937;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #374151;
}
</style>
