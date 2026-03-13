<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import LogViewerSidebar from '@/Components/Molecules/LogViewer/LogViewerSidebar.vue'
import LogViewerTopbar from '@/Components/Molecules/LogViewer/LogViewerTopbar.vue'
import LogViewerTable from '@/Components/Organisms/LogViewer/LogViewerTable.vue'
import LogViewerDetail from '@/Components/Organisms/LogViewer/LogViewerDetail.vue'
import axios from 'axios'

// State
const logs = ref([])
const files = ref([])
const statistics = ref({})
const loading = ref(false)
const selectedFile = ref('laravel.log')
const selectedLog = ref(null)
const showDetail = ref(false)

const filters = ref({
    search: '',
    level: '',
    type: 'laravel',
    limit: 1000
})

// Simple Debounce Implementation
const debounce = (fn, delay) => {
    let timeoutId
    return (...args) => {
        if (timeoutId) clearTimeout(timeoutId)
        timeoutId = setTimeout(() => {
            fn(...args)
        }, delay)
    }
}

// Methods
const loadFiles = async () => {
    try {
        const response = await axios.get('/api/v1/logs/files')
        files.value = response.data.files
        
        // Selecionar o mais recente se nada selecionado
        if (files.value.length > 0 && !selectedFile.value) {
            selectedFile.value = files.value[0].name
        }
    } catch (error) {
        console.error('Error loading files:', error)
    }
}

const loadLogs = async () => {
    loading.value = true
    try {
        const params = {
            ...filters.value,
            file: selectedFile.value
        }
        
        // Se level for 'error', queremos incluir critical e emergency
        if (params.level === 'error') {
            params.include_higher_levels = true
        }

        const response = await axios.get('/api/v1/logs', { params })
        logs.value = response.data.logs
        
        // Carrega estatísticas se necessário
        loadStatistics()
    } catch (error) {
        console.error('Error loading logs:', error)
    } finally {
        loading.value = false
    }
}

const loadStatistics = async () => {
    try {
        const response = await axios.get('/api/v1/logs/statistics', {
            params: { file: selectedFile.value }
        })
        statistics.value = response.data.statistics
    } catch (error) {
        console.error('Error loading statistics:', error)
    }
}

const selectFile = (fileName) => {
    selectedFile.value = fileName
    loadLogs()
}

const selectLog = (log) => {
    selectedLog.value = log
    showDetail.value = true
}

// Watchers
const debouncedLoadLogs = debounce(() => {
    loadLogs()
}, 300)

watch(() => filters.value.search, () => {
    debouncedLoadLogs()
})

watch(() => filters.value.level, () => {
    loadLogs()
})

// Lifecycle
onMounted(() => {
    loadFiles()
    loadLogs()
})
</script>

<template>
    <Head title="Log Viewer Premium" />


        <div class="h-[calc(100vh-65px)] flex overflow-hidden bg-[#0b0e14] text-gray-300">
            <!-- Sidebar -->
            <LogViewerSidebar 
                :files="files" 
                :selected-file="selectedFile" 
                @select-file="selectFile" 
            />

            <!-- Main Content -->
            <div class="flex-1 flex flex-col min-w-0">
                <LogViewerTopbar 
                    v-model:filters="filters" 
                    :stats="statistics"
                    :loading="loading"
                    @refresh="loadLogs"
                />

                <LogViewerTable 
                    :logs="logs" 
                    :loading="loading"
                    :filters="filters"
                    @select-log="selectLog"
                />
            </div>

            <!-- Modal Detalhe -->
            <LogViewerDetail
                :show="showDetail"
                :log="selectedLog"
                @close="showDetail = false"
            />
        </div>

</template>

<style>
/* Reset global para o Log Viewer preencher a tela */
body {
    overflow: hidden;
}
</style>
