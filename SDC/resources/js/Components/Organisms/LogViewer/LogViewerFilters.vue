<template>
  <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6 mb-6">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
      Filtros de Logs
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Data Inicial -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Data Inicial
        </label>
        <DatePicker
          :model-value="filters.start_date"
          @update:model-value="updateFilter('start_date', $event)"
        />
      </div>

      <!-- Data Final -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Data Final
        </label>
        <DatePicker
          :model-value="filters.end_date"
          @update:model-value="updateFilter('end_date', $event)"
        />
      </div>

      <!-- Tipo -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Tipo de Log
        </label>
        <select
          :value="filters.type"
          @change="updateFilter('type', $event.target.value)"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="">Todos</option>
          <option value="laravel">Laravel</option>
          <option value="events">Events</option>
          <option value="critical">Critical</option>
          <option value="queries">Queries</option>
          <option value="jobs">Jobs</option>
        </select>
      </div>

      <!-- Nível -->
      <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
          Nível
        </label>
        <select
          :value="filters.level"
          @change="updateFilter('level', $event.target.value)"
          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
        >
          <option value="">Todos</option>
          <option value="debug">Debug</option>
          <option value="info">Info</option>
          <option value="notice">Notice</option>
          <option value="warning">Warning</option>
          <option value="error">Error</option>
          <option value="critical">Critical</option>
          <option value="alert">Alert</option>
          <option value="emergency">Emergency</option>
        </select>
      </div>
    </div>

    <!-- Busca -->
    <div class="mt-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Buscar
      </label>
      <div class="relative">
        <input
          type="text"
          :value="filters.search"
          @input="updateFilter('search', $event.target.value)"
          placeholder="Buscar em mensagens e contexto..."
          class="w-full px-3 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
        />
        <svg
          class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>
    </div>

    <!-- Ações -->
    <div class="mt-4 flex justify-between items-center">
      <div class="flex gap-2">
        <button
          @click="$emit('apply-filters')"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
        >
          Aplicar Filtros
        </button>
        <button
          @click="$emit('reset-filters')"
          class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600"
        >
          Limpar
        </button>
      </div>

      <!-- Atalhos rápidos -->
      <div class="flex gap-2">
        <button
          @click="setQuickFilter('today')"
          class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          Hoje
        </button>
        <button
          @click="setQuickFilter('yesterday')"
          class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          Ontem
        </button>
        <button
          @click="setQuickFilter('week')"
          class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          7 dias
        </button>
        <button
          @click="setQuickFilter('month')"
          class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600"
        >
          30 dias
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineEmits } from 'vue'
import DatePicker from '@/Components/Form/DatePicker.vue'

const props = defineProps({
  filters: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['update-filter', 'apply-filters', 'reset-filters'])

const updateFilter = (key, value) => {
  emit('update-filter', { key, value })
}

const setQuickFilter = (range) => {
  const now = new Date()
  const today = now.toISOString().split('T')[0]

  let startDate = today
  const endDate = today

  switch (range) {
    case 'today':
      startDate = today
      break
    case 'yesterday':
      const yesterday = new Date(now)
      yesterday.setDate(yesterday.getDate() - 1)
      startDate = yesterday.toISOString().split('T')[0]
      break
    case 'week':
      const weekAgo = new Date(now)
      weekAgo.setDate(weekAgo.getDate() - 7)
      startDate = weekAgo.toISOString().split('T')[0]
      break
    case 'month':
      const monthAgo = new Date(now)
      monthAgo.setDate(monthAgo.getDate() - 30)
      startDate = monthAgo.toISOString().split('T')[0]
      break
  }

  emit('update-filter', { key: 'start_date', value: startDate })
  emit('update-filter', { key: 'end_date', value: endDate })
  emit('apply-filters')
}
</script>
