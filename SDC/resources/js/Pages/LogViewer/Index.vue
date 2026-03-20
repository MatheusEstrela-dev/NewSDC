<script setup>
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import SidebarOnlyLayout from '@/Layouts/SidebarOnlyLayout.vue'
import LogViewerTopbar from '@/Components/Molecules/LogViewer/LogViewerTopbar.vue'
import LogViewerTable from '@/Components/Organisms/LogViewer/LogViewerTable.vue'
import LogViewerDetail from '@/Components/Organisms/LogViewer/LogViewerDetail.vue'

defineOptions({ layout: SidebarOnlyLayout })

const props = defineProps({
    initialLogs: {
        type: Array,
        default: () => []
    },
    initialStats: {
        type: Object,
        default: () => ({})
    },
    availableLayers: {
        type: Array,
        default: () => []
    },
    availableLevels: {
        type: Array,
        default: () => []
    },
    availableTypes: {
        type: Array,
        default: () => []
    }
})

const loading = ref(false)
const selectedLog = ref(null)
const showDetail = ref(false)

const urlParams = new URLSearchParams(window.location.search)

const filters = ref({
    search: urlParams.get('search') || '',
    level: urlParams.get('level') || '',
    layer: urlParams.get('layer') || '',
    type: urlParams.get('type') || 'laravel',
    date_from: urlParams.get('date_from') || '',
    date_to: urlParams.get('date_to') || '',
    errors_only: urlParams.get('errors_only') === '1',
    limit: parseInt(urlParams.get('limit')) || 100
})

// Funções de Gestão (Engrenagem)
const handleAction = (action, params = {}) => {
    loading.value = true
    
    if (action === 'download') {
        const url = window.route('log-viewer.download', { file: params.file || 'laravel.log' })
        window.open(url, '_blank')
        loading.value = false
        return
    }

    router.post(window.route(`log-viewer.${action}`), params, {
        onEnter: () => loading.value = true,
        onFinish: () => {
            loading.value = false
            reloadData()
        }
    })
}

const debounce = (fn, delay) => {
    let timeoutId
    return (...args) => {
        if (timeoutId) clearTimeout(timeoutId)
        timeoutId = setTimeout(() => fn(...args), delay)
    }
}

// ÚNICA função para carregar dados via backend
const reloadData = () => {
    loading.value = true
    const query = { ...filters.value }
    if (query.errors_only) {
        query.errors_only = '1'
    } else {
        delete query.errors_only
    }
    
    Object.keys(query).forEach(key => {
        if (query[key] === '' || query[key] === null) {
            delete query[key]
        }
    })

    router.get(window.route('log-viewer.index'), query, {
        preserveState: true,
        preserveScroll: true,
        only: ['initialLogs', 'initialStats'],
        onFinish: () => loading.value = false
    })
}

const selectLog = (log) => {
    selectedLog.value = log
    showDetail.value = true
}

const debouncedReload = debounce(() => reloadData(), 300)

watch(() => filters.value.search, () => debouncedReload())
watch(() => filters.value.level, () => reloadData())
watch(() => filters.value.layer, () => reloadData())
watch(() => filters.value.type, () => reloadData())
watch(() => filters.value.errors_only, () => reloadData())
watch(() => filters.value.date_from, () => reloadData())
watch(() => filters.value.date_to, () => reloadData())

const errorCount = computed(() => {
    const byLevel = props.initialStats?.by_level || {}
    return (byLevel.error || 0) + (byLevel.critical || 0) + (byLevel.emergency || 0) + (byLevel.alert || 0)
})
const totalCount = computed(() => props.initialStats?.total_logs || 0)
const todayCount = computed(() => {
    const today = new Date().toISOString().split('T')[0]
    return props.initialStats?.by_day?.[today] || 0
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

            <div class="ml-auto flex items-center gap-6">
                <!-- Data Range Info (Read-only or small indicators if needed) -->
                <div v-if="filters.date_from || filters.date_to" class="text-[10px] text-gray-500 flex gap-2">
                    <span v-if="filters.date_from">Desde: {{ filters.date_from }}</span>
                    <span v-if="filters.date_to">Até: {{ filters.date_to }}</span>
                </div>
            </div>
        </div>

        <!-- Topbar com Level + Search + Gestão -->
        <LogViewerTopbar
            v-model:filters="filters"
            :stats="initialStats"
            :loading="loading"
            :levels="availableLevels"
            :layers="availableLayers"
            :types="availableTypes"
            @refresh="reloadData"
            @action="handleAction"
        />

        <!-- Tabela -->
        <LogViewerTable
            :logs="initialLogs"
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
