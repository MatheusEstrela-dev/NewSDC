<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Logs -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
            Total de Logs
          </p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ stats.total_logs || 0 }}
          </p>
        </div>
        <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
          <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Errors -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
            Erros
          </p>
          <p class="text-2xl font-bold text-red-600 dark:text-red-400">
            {{ errorCount }}
          </p>
        </div>
        <div class="p-3 bg-red-100 dark:bg-red-900 rounded-full">
          <svg class="w-6 h-6 text-red-600 dark:text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Warnings -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
            Avisos
          </p>
          <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
            {{ warningCount }}
          </p>
        </div>
        <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
          <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Error Rate -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
            Taxa de Erro
          </p>
          <p class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ stats.error_rate || 0 }}%
          </p>
        </div>
        <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
          <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
        </div>
      </div>
    </div>
  </div>

  <!-- Charts Row -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Logs por Nível -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Logs por Nível
      </h3>
      <div class="space-y-3">
        <div
          v-for="(count, level) in stats.by_level"
          :key="level"
          class="flex items-center justify-between"
        >
          <div class="flex items-center gap-2">
            <span
              class="w-3 h-3 rounded-full"
              :class="getLevelColorDot(level)"
            />
            <span class="text-sm text-gray-700 dark:text-gray-300 capitalize">
              {{ level }}
            </span>
          </div>
          <div class="flex items-center gap-3">
            <div class="w-32 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
              <div
                class="h-2 rounded-full"
                :class="getLevelColorBar(level)"
                :style="{ width: `${(count / stats.total_logs) * 100}%` }"
              />
            </div>
            <span class="text-sm font-semibold text-gray-900 dark:text-white w-12 text-right">
              {{ count }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Top Erros -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
        Top Erros
      </h3>
      <div class="space-y-3">
        <div
          v-for="(error, index) in stats.top_errors?.slice(0, 5)"
          :key="index"
          class="border-b border-gray-200 dark:border-gray-700 pb-2 last:border-0"
        >
          <div class="flex justify-between items-start gap-2">
            <p class="text-sm text-gray-900 dark:text-gray-100 truncate flex-1">
              {{ error.message }}
            </p>
            <span class="text-sm font-semibold text-red-600 dark:text-red-400 whitespace-nowrap">
              {{ error.count }}x
            </span>
          </div>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Última ocorrência: {{ formatTimestamp(error.last_occurrence) }}
          </p>
        </div>
        <div v-if="!stats.top_errors || stats.top_errors.length === 0" class="text-center py-4">
          <p class="text-sm text-gray-500 dark:text-gray-400">
            Nenhum erro encontrado
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({}),
  },
})

const errorCount = computed(() => {
  const byLevel = props.stats.by_level || {}
  return (byLevel.error || 0) + (byLevel.critical || 0) + (byLevel.emergency || 0) + (byLevel.alert || 0)
})

const warningCount = computed(() => {
  const byLevel = props.stats.by_level || {}
  return byLevel.warning || 0
})

const formatTimestamp = (timestamp) => {
  if (!timestamp) return '-'
  const date = new Date(timestamp)
  return date.toLocaleString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getLevelColorDot = (level) => {
  const colors = {
    debug: 'bg-gray-400',
    info: 'bg-blue-500',
    notice: 'bg-cyan-500',
    warning: 'bg-yellow-500',
    error: 'bg-orange-500',
    critical: 'bg-red-600',
    alert: 'bg-red-700',
    emergency: 'bg-red-800',
  }
  return colors[level] || 'bg-gray-400'
}

const getLevelColorBar = (level) => {
  const colors = {
    debug: 'bg-gray-400',
    info: 'bg-blue-500',
    notice: 'bg-cyan-500',
    warning: 'bg-yellow-500',
    error: 'bg-orange-500',
    critical: 'bg-red-600',
    alert: 'bg-red-700',
    emergency: 'bg-red-800',
  }
  return colors[level] || 'bg-gray-400'
}
</script>
