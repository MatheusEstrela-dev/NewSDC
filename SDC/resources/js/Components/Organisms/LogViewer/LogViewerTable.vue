<script setup>
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
    if (!level) return 'text-gray-400'
    const strLevel = String(level).toLowerCase()
    const colors = {
        debug: 'text-gray-400',
        info: 'text-blue-400',
        notice: 'text-cyan-400',
        warning: 'text-yellow-400',
        error: 'text-orange-400',
        critical: 'text-red-500 font-bold',
        alert: 'text-red-600 font-bold',
        emergency: 'text-red-700 font-bold'
    }
    return colors[strLevel] || 'text-gray-400'
}

const getLevelBg = (level) => {
    if (!level) return 'bg-gray-500'
    const strLevel = String(level).toLowerCase()
    const colors = {
        debug: 'bg-gray-500',
        info: 'bg-blue-500',
        notice: 'bg-cyan-500',
        warning: 'bg-yellow-500',
        error: 'bg-orange-500',
        critical: 'bg-red-500',
        alert: 'bg-red-600',
        emergency: 'bg-red-700'
    }
    return colors[strLevel] || 'bg-gray-500'
}

const getLayerColor = (layer, logType) => {
    if (!layer) return 'bg-gray-700 text-gray-400'
    // Container Docker — cor especial por tipo de serviço
    if (logType === 'docker') {
        if (layer.includes('nginx'))   return 'bg-green-900/50 text-green-300'
        if (layer.includes('redis'))   return 'bg-red-900/50 text-red-300'
        if (layer.includes('db') || layer.includes('mysql')) return 'bg-yellow-900/50 text-yellow-300'
        if (layer.includes('queue'))   return 'bg-orange-900/50 text-orange-300'
        if (layer.includes('app'))     return 'bg-blue-900/50 text-blue-300'
        return 'bg-cyan-900/40 text-cyan-300'
    }
    const colors = {
        'Controller': 'bg-blue-900/50 text-blue-300',
        'Service':    'bg-purple-900/50 text-purple-300',
        'Repository': 'bg-green-900/50 text-green-300',
        'Model':      'bg-yellow-900/50 text-yellow-300',
        'Middleware': 'bg-orange-900/50 text-orange-300',
        'Request':    'bg-cyan-900/50 text-cyan-300',
        'DTO':        'bg-pink-900/50 text-pink-300',
    }
    return colors[layer] || 'bg-gray-700 text-gray-400'
}

const formatTime = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleTimeString('pt-BR', { hour12: false })
}

const formatDate = (dateStr) => {
    if (!dateStr) return ''
    const date = new Date(dateStr)
    return date.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

const getShortClass = (className) => {
    if (!className) return ''
    return String(className).split('\\').pop()
}

const getFilePath = (log) => {
    return log.file_path || log.file || null
}

const getFileName = (log) => {
    const path = getFilePath(log)
    if (!path) return null
    return String(path).split('/').pop().split('\\').pop()
}

const getVSCodeLink = (log) => {
    const filePath = getFilePath(log)
    const line = log.line || 1
    if (!filePath) return null
    return `vscode://file/${filePath}:${line}`
}

const formatMs = (ms) => {
    if (!ms) return null
    if (ms >= 1000) return (ms / 1000).toFixed(1) + 's'
    return Math.round(ms) + 'ms'
}

const timeBadgeClass = (ms, threshold) => {
    const t = threshold || 2000
    if (ms < t / 2)   return 'bg-green-900/50 text-green-400'
    if (ms < t)       return 'bg-yellow-900/50 text-yellow-400'
    if (ms < t * 2)   return 'bg-orange-900/50 text-orange-400'
    return                    'bg-red-900/50 text-red-400'
}

const queryTypeBadge = (type) => {
    const map = {
        'SELECT': 'text-blue-400',
        'INSERT': 'text-green-400',
        'UPDATE': 'text-yellow-400',
        'DELETE': 'text-red-400',
        'DROP':   'text-red-300',
    }
    return map[type] || 'text-gray-400'
}
</script>

<template>
    <div class="flex-1 overflow-hidden flex flex-col bg-[#0b0e14] relative">
        <!-- Empty State -->
        <div v-if="!loading && logs.length === 0" class="flex-1 flex flex-col items-center justify-center text-gray-600 p-8 text-center">
            <svg class="w-16 h-16 mb-4 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1.1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="text-lg font-medium text-gray-500">Nenhum log encontrado</h3>
            <p class="text-sm max-w-xs mt-1">Ajuste os filtros ou aguarde novos logs do sistema.</p>
        </div>

        <!-- Table -->
        <div v-else class="flex-1 overflow-auto custom-scrollbar">
            <table class="w-full border-collapse text-xs font-mono">
                <thead class="sticky top-0 bg-[#0b0e14] z-10 border-b border-gray-800 shadow-sm">
                    <tr class="text-left text-gray-500 uppercase tracking-tighter h-8">
                        <th class="px-3 font-semibold w-20">Level</th>
                        <th class="px-2 font-semibold w-24">Layer</th>
                        <th class="px-2 font-semibold w-44">Origem</th>
                        <th class="px-2 font-semibold w-36">Arquivo</th>
                        <th class="px-2 font-semibold w-12">Linha</th>
                        <th class="px-2 font-semibold">Mensagem</th>
                        <th class="px-2 font-semibold w-16 text-right">Data</th>
                        <th class="px-3 font-semibold w-14 text-right">Hora</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(log, index) in logs"
                        :key="log.id || index"
                        @click="emit('select-log', log)"
                        class="border-b border-gray-800/30 hover:bg-gray-800/40 cursor-pointer group transition-colors h-9"
                    >
                        <!-- Level -->
                        <td class="px-3 whitespace-nowrap">
                            <span :class="getLevelColor(log.level)" class="flex items-center gap-1.5 uppercase font-bold text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full" :class="getLevelBg(log.level)"></span>
                                {{ log.level }}
                            </span>
                        </td>

                        <!-- Layer -->
                        <td class="px-2">
                            <span v-if="log.query_type"
                                class="px-1.5 py-0.5 rounded text-[10px] font-bold"
                                :class="queryTypeBadge(log.query_type)">
                                {{ log.query_type }}
                            </span>
                            <span v-else-if="log.layer"
                                class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                :class="getLayerColor(log.layer, log.log_type)">
                                {{ log.layer }}
                            </span>
                            <span v-else class="text-gray-600">-</span>
                        </td>

                        <!-- Origem (Class.method) -->
                        <td class="px-2 text-gray-400 truncate max-w-[180px]" :title="log.class">
                            <span v-if="log.class || log.method">
                                <span class="text-gray-300">{{ getShortClass(log.class) }}</span>
                                <span v-if="log.method" class="text-gray-500">.{{ log.method }}()</span>
                            </span>
                            <span v-else class="text-gray-600">-</span>
                        </td>

                        <!-- Arquivo (com link clicavel para VSCode) -->
                        <td class="px-2 text-gray-400 truncate max-w-[150px]">
                            <a
                                v-if="getFileName(log)"
                                :href="getVSCodeLink(log)"
                                :title="getFilePath(log)"
                                class="text-cyan-400 hover:text-cyan-300 hover:underline cursor-pointer"
                                @click.stop
                            >
                                {{ getFileName(log) }}
                            </a>
                            <span v-else class="text-gray-600">-</span>
                        </td>

                        <!-- Linha -->
                        <td class="px-2 text-gray-500 font-mono text-[10px]">
                            <span v-if="log.line" class="text-yellow-400">:{{ log.line }}</span>
                            <span v-else class="text-gray-600">-</span>
                        </td>

                        <!-- Mensagem -->
                        <td class="px-2 truncate max-w-0 text-gray-300 group-hover:text-white transition-colors">
                            <span v-if="log.time_ms"
                                class="inline-flex items-center mr-2 px-1.5 py-0.5 rounded text-[9px] font-mono font-bold shrink-0"
                                :class="timeBadgeClass(log.time_ms, log.threshold_ms)">
                                {{ formatMs(log.time_ms) }}
                            </span>
                            {{ log.message }}
                        </td>

                        <!-- Data -->
                        <td class="px-2 text-right text-gray-500 whitespace-nowrap">
                            {{ formatDate(log.timestamp || log.created_at) }}
                        </td>

                        <!-- Hora -->
                        <td class="px-3 text-right text-gray-500 whitespace-nowrap">
                            {{ formatTime(log.timestamp || log.created_at) }}
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Loading Overlay -->
            <div v-if="loading" class="absolute inset-0 bg-[#0b0e14]/50 backdrop-blur-[1px] flex items-center justify-center z-20">
                <div class="flex flex-col items-center">
                    <div class="w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
                    <span class="mt-4 text-xs font-medium text-blue-500 tracking-widest uppercase">Carregando logs...</span>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="px-6 py-2 bg-gray-900 border-t border-gray-800 flex justify-between items-center text-[10px] text-gray-600 shrink-0">
            <div class="flex gap-4">
                <span>Registros: <span class="text-gray-400 font-mono">{{ logs.length }}</span></span>
            </div>
            <div class="flex gap-4 items-center">
                <span class="hidden sm:inline italic">SDC Backend Logging System</span>
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
