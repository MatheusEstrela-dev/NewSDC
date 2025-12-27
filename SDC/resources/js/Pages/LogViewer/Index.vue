<template>
  <Head title="Log Viewer" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Log Viewer
        </h2>
        <div class="flex gap-2">
          <button
            @click="toggleAutoRefresh"
            class="px-3 py-2 text-sm rounded-md"
            :class="autoRefresh
              ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'
              : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'"
          >
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Auto-refresh {{ autoRefresh ? 'ON' : 'OFF' }}
          </button>
          <button
            @click="showStats = !showStats"
            class="px-3 py-2 text-sm bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-md"
          >
            {{ showStats ? 'Ocultar' : 'Mostrar' }} Estatísticas
          </button>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Filters -->
        <LogViewerFilters
          :filters="filters"
          @update-filter="updateFilter"
          @apply-filters="loadLogs"
          @reset-filters="resetFilters"
        />

        <!-- Statistics -->
        <LogViewerStats
          v-if="showStats"
          :stats="statistics"
        />

        <!-- Table -->
        <LogViewerTable
          :logs="logs"
          :total="total"
          :loading="loading"
          :filters="filters"
          @select-log="selectLog"
          @refresh="loadLogs"
          @export="exportLogs"
        />

        <!-- Detail Modal -->
        <LogViewerDetail
          :show="showDetail"
          :log="selectedLog"
          @close="closeDetail"
        />
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import LogViewerFilters from '@/Components/Organisms/LogViewer/LogViewerFilters.vue'
import LogViewerTable from '@/Components/Organisms/LogViewer/LogViewerTable.vue'
import LogViewerDetail from '@/Components/Organisms/LogViewer/LogViewerDetail.vue'
import LogViewerStats from '@/Components/Organisms/LogViewer/LogViewerStats.vue'
import axios from 'axios'

// State
const logs = ref([])
const total = ref(0)
const loading = ref(false)
const showDetail = ref(false)
const selectedLog = ref(null)
const showStats = ref(true)
const statistics = ref({})
const autoRefresh = ref(false)
let autoRefreshInterval = null

// Filters
const filters = ref({
  start_date: getDateDaysAgo(7),
  end_date: getTodayDate(),
  type: '',
  level: '',
  search: '',
  limit: 1000,
})

// Methods
function getDateDaysAgo(days) {
  const date = new Date()
  date.setDate(date.getDate() - days)
  return date.toISOString().split('T')[0]
}

function getTodayDate() {
  return new Date().toISOString().split('T')[0]
}

function updateFilter({ key, value }) {
  filters.value[key] = value
}

function resetFilters() {
  filters.value = {
    start_date: getDateDaysAgo(7),
    end_date: getTodayDate(),
    type: '',
    level: '',
    search: '',
    limit: 1000,
  }
  loadLogs()
}

async function loadLogs() {
  loading.value = true

  try {
    const params = new URLSearchParams()

    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) {
        params.append(key, value)
      }
    })

    const response = await axios.get(`/api/v1/logs?${params.toString()}`)

    logs.value = response.data.logs
    total.value = response.data.total
  } catch (error) {
    console.error('Erro ao carregar logs:', error)
    alert('Erro ao carregar logs')
  } finally {
    loading.value = false
  }
}

async function loadStatistics() {
  try {
    const params = new URLSearchParams()

    if (filters.value.start_date) params.append('start_date', filters.value.start_date)
    if (filters.value.end_date) params.append('end_date', filters.value.end_date)
    if (filters.value.type) params.append('type', filters.value.type)

    const response = await axios.get(`/api/v1/logs/statistics?${params.toString()}`)

    statistics.value = response.data.statistics
  } catch (error) {
    console.error('Erro ao carregar estatísticas:', error)
  }
}

function selectLog(log) {
  selectedLog.value = log
  showDetail.value = true
}

function closeDetail() {
  showDetail.value = false
  selectedLog.value = null
}

async function exportLogs() {
  try {
    const params = new URLSearchParams()

    Object.entries(filters.value).forEach(([key, value]) => {
      if (value) {
        params.append(key, value)
      }
    })

    const response = await axios.get(`/api/v1/logs?${params.toString()}`, {
      responseType: 'blob',
    })

    // Se a resposta for JSON, converte para blob
    let blob
    if (response.data instanceof Blob) {
      blob = response.data
    } else {
      const jsonData = JSON.stringify(logs.value, null, 2)
      blob = new Blob([jsonData], { type: 'application/json' })
    }

    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `logs-${filters.value.start_date}-${filters.value.end_date}.json`
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)
  } catch (error) {
    console.error('Erro ao exportar logs:', error)
    alert('Erro ao exportar logs')
  }
}

function toggleAutoRefresh() {
  autoRefresh.value = !autoRefresh.value

  if (autoRefresh.value) {
    autoRefreshInterval = setInterval(() => {
      loadLogs()
      if (showStats.value) {
        loadStatistics()
      }
    }, 30000) // 30 segundos
  } else {
    if (autoRefreshInterval) {
      clearInterval(autoRefreshInterval)
      autoRefreshInterval = null
    }
  }
}

// Lifecycle
onMounted(() => {
  loadLogs()
  loadStatistics()
})

onUnmounted(() => {
  if (autoRefreshInterval) {
    clearInterval(autoRefreshInterval)
  }
})
</script>
