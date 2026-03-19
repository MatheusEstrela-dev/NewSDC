<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import SidebarOnlyLayout from '@/Layouts/SidebarOnlyLayout.vue'
import LogViewerTopbar from '@/Components/Molecules/LogViewer/LogViewerTopbar.vue'
import LogViewerTable from '@/Components/Organisms/LogViewer/LogViewerTable.vue'
import LogViewerDetail from '@/Components/Organisms/LogViewer/LogViewerDetail.vue'
import axios from 'axios'

defineOptions({ layout: SidebarOnlyLayout })

const BACKEND_API = import.meta.env.VITE_SDC_API_URL || 'http://localhost:8000'

const logs = ref([])
const layers = ref([])
const levels = ref([])
const statistics = ref({})
const loading = ref(false)
const selectedLog = ref(null)
const showDetail = ref(false)

const filters = ref({
    search: '',
    level: '',
    layer: '',
    date_from: '',
    date_to: '',
    errors_only: false,
    limit: 100
})

const debounce = (fn, delay) => {
    let timeoutId
    return (...args) => {
        if (timeoutId) clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn(...args), delay)
    }
}

const loadLogs = async () => {
    loading.value = true
    try {
        const params = new URLSearchParams()
        if (filters.value.level) params.append('level', filters.value.level)
        if (filters.value.layer) params.append('layer', filters.value.layer)
        if (filters.value.search) params.append('search', filters.value.search)
        if (filters.value.date_from) params.append('date_from', filters.value.date_from)
        if (filters.value.date_to) params.append('date_to', filters.value.date_to)
        if (filters.value.errors_only) params.append('errors_only', '1')
        params.append('limit', filters.value.limit)

        const response = await axios.get(`${BACKEND_API}/api/v1/logs?${params.toString()}`)
        logs.value = response.data.logs?.data || response.data.logs || []
    } catch (error) {
        console.error('Erro ao carregar logs:', error)
        logs.value = []
    } finally {
        loading.value = false
    }
}

const loadStats = async () => {
    try {
        const response = await axios.get(`${BACKEND_API}/api/v1/logs/stats`)
        statistics.value = response.data.stats || {}
    } catch (error) {
        statistics.value = {}
    }
}

const loadLayers = async () => {
    try {
        const response = await axios.get(`${BACKEND_API}/api/v1/logs/layers`)
        layers.value = response.data.layers || []
    } catch (error) {
        layers.value = []
    }
}

const loadLevels = async () => {
    try {
        const response = await axios.get(`${BACKEND_API}/api/v1/logs/levels`)
        levels.value = response.data.levels || []
    } catch (error) {
        levels.value = ['debug', 'info', 'warning', 'error', 'critical']
    }
}

const selectLog = (log) => {
    selectedLog.value = log
    showDetail.value = true
}

const debouncedLoadLogs = debounce(() => loadLogs(), 300)

watch(() => filters.value.search, () => debouncedLoadLogs())
watch(() => filters.value.level, () => loadLogs())
watch(() => filters.value.layer, () => loadLogs())
watch(() => filters.value.errors_only, () => loadLogs())

const errorCount = computed(() => statistics.value?.errors_today || 0)
const totalCount = computed(() => statistics.value?.total || 0)
const todayCount = computed(() => statistics.value?.today || 0)

onMounted(() => {
    loadLayers()
    loadLevels()
    loadStats()
    loadLogs()
})
</script>

<template>
    <Head title="Log Viewer" />

    <div class="h-screen flex flex-col overflow-hidden bg-[#0b0e14] text-gray-300">
        <!-- Stats Bar -->
        <div class="px-4 py-3 bg-[#0d1117] border-b border-gray-800 flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 uppercase tracking-wider">Total</span>
                <span class="text-lg font-bold text-white">{{ totalCount }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 uppercase tracking-wider">Hoje</span>
                <span class="text-lg font-bold text-blue-400">{{ todayCount }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 uppercase tracking-wider">Erros Hoje</span>
                <span class="text-lg font-bold" :class="errorCount > 0 ? 'text-red-400' : 'text-green-400'">
                    {{ errorCount }}
                </span>
            </div>

            <!-- Filtro por Layer -->
            <div class="ml-auto flex items-center gap-3">
                <select
                    v-model="filters.layer"
                    class="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded text-sm text-gray-300 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Todas as camadas</option>
                    <option v-for="layer in layers" :key="layer" :value="layer">{{ layer }}</option>
                </select>

                <!-- Date Range -->
                <input
                    type="date"
                    v-model="filters.date_from"
                    @change="loadLogs"
                    class="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded text-sm text-gray-300"
                    placeholder="Data inicial"
                />
                <span class="text-gray-600">-</span>
                <input
                    type="date"
                    v-model="filters.date_to"
                    @change="loadLogs"
                    class="px-3 py-1.5 bg-gray-800 border border-gray-700 rounded text-sm text-gray-300"
                    placeholder="Data final"
                />

                <!-- Errors Only -->
                <label class="flex items-center gap-2 text-sm text-gray-400 cursor-pointer">
                    <input
                        type="checkbox"
                        v-model="filters.errors_only"
                        class="rounded bg-gray-800 border-gray-600 text-red-500"
                    />
                    Apenas erros
                </label>
            </div>
        </div>

        <!-- Topbar com Level + Search -->
        <LogViewerTopbar
            v-model:filters="filters"
            :stats="statistics"
            :loading="loading"
            :levels="levels"
            @refresh="loadLogs"
        />

        <!-- Tabela -->
        <LogViewerTable
            :logs="logs"
            :loading="loading"
            :filters="filters"
            @select-log="selectLog"
        />

        <!-- Modal Detalhe -->
        <LogViewerDetail
            :show="showDetail"
            :log="selectedLog"
            @close="showDetail = false"
        />
    </div>
</template>

<style scoped>
</style>
