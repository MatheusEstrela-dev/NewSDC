<script setup>
import { computed } from 'vue'
import Dropdown from '@/Components/Dropdown.vue'
import DatePicker from '@/Components/Form/DatePicker.vue'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        required: true
    },
    loading: Boolean,
    levels: {
        type: Array,
        default: () => ['debug', 'info', 'warning', 'error', 'critical']
    },
    types: {
        type: Array,
        default: () => []
    },
    layers: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:filters', 'refresh', 'action'])

const counts = computed(() => {
    return {
        debug: props.stats.by_level?.debug || 0,
        info: props.stats.by_level?.info || 0,
        warning: props.stats.by_level?.warning || 0,
        error: (props.stats.by_level?.error || 0) + (props.stats.by_level?.critical || 0) + (props.stats.by_level?.emergency || 0)
    }
})

const toggleLevel = (level) => {
    const newLevel = props.filters.level === level ? '' : level
    emit('update:filters', { ...props.filters, level: newLevel })
}
</script>

<template>
    <div class="bg-gray-900/50 backdrop-blur-md border-b border-gray-800 px-6 py-4 flex flex-wrap items-center justify-between gap-4 sticky top-0 z-10">
        <!-- Níveis de Status -->
        <div class="flex flex-wrap gap-2">
            <button 
                @click="toggleLevel('debug')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border"
                :class="filters.level === 'debug' 
                    ? 'bg-gray-700 border-gray-600 text-white ring-1 ring-gray-500' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                Debug: {{ counts.debug.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('info')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border"
                :class="filters.level === 'info' 
                    ? 'bg-blue-900/40 border-blue-800 text-blue-100 ring-1 ring-blue-700' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Info: {{ counts.info.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('warning')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border"
                :class="filters.level === 'warning' 
                    ? 'bg-yellow-900/40 border-yellow-800 text-yellow-100 ring-1 ring-yellow-700' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                Warning: {{ counts.warning.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('error')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold transition-all border"
                :class="filters.level === 'error' 
                    ? 'bg-red-900/40 border-red-800 text-red-100 ring-1 ring-red-700' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                Error: {{ counts.error.toLocaleString() }}
            </button>
        </div>

        <!-- Busca e Ações -->
        <div class="flex items-center gap-3 flex-1 min-w-[300px] justify-end">
            <div class="relative flex-1 max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    :value="filters.search"
                    @input="e => emit('update:filters', { ...filters, search: e.target.value })"
                    type="text"
                    placeholder="Search logs... (supports Regex)"
                    class="block w-full pl-10 pr-3 py-1.5 bg-gray-800 border-gray-700 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500 transition-colors border"
                />
            </div>
            
            <!-- Layer Selector -->
            <div class="relative min-w-[150px]">
                <select
                    :value="filters.layer"
                    @change="e => emit('update:filters', { ...filters, layer: e.target.value })"
                    class="block w-full pl-3 pr-10 py-1.5 bg-gray-800 border-gray-700 rounded-lg text-xs text-gray-200 focus:ring-blue-500 focus:border-blue-500 transition-colors border appearance-none"
                    title="Filter by Layer"
                >
                    <option value="">Todas as camadas</option>
                    <option v-for="layer in layers" :key="layer" :value="layer">
                        {{ layer }}
                    </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-3 w-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Log Source Selector -->
            <div class="relative min-w-[120px]">
                <select
                    :value="filters.type"
                    @change="e => emit('update:filters', { ...filters, type: e.target.value })"
                    class="block w-full pl-3 pr-10 py-1.5 bg-gray-800 border-gray-700 rounded-lg text-xs text-gray-200 focus:ring-blue-500 focus:border-blue-500 transition-colors border appearance-none"
                    title="Log Source"
                >
                    <option v-for="type in types" :key="type" :value="type">
                        {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                    </option>
                </select>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <svg class="h-3 w-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <!-- Date Range -->
            <div class="flex items-center gap-2">
                <DatePicker
                    :model-value="filters.date_from"
                    @update:model-value="v => emit('update:filters', { ...filters, date_from: v })"
                    extra-class="min-w-[110px]"
                />
                <span class="text-gray-600">-</span>
                <DatePicker
                    :model-value="filters.date_to"
                    @update:model-value="v => emit('update:filters', { ...filters, date_to: v })"
                    extra-class="min-w-[110px]"
                />
            </div>

            <!-- Errors Only -->
            <label class="flex items-center gap-2 text-xs text-gray-400 cursor-pointer hover:text-gray-300 transition-colors shrink-0">
                <input
                    type="checkbox"
                    :checked="filters.errors_only"
                    @change="e => emit('update:filters', { ...filters, errors_only: e.target.checked })"
                    class="rounded bg-gray-800 border-gray-600 text-red-500 focus:ring-offset-0 focus:ring-opacity-0"
                />
                <span>Apenas erros</span>
            </label>

            <button 
                @click="emit('refresh')"
                :disabled="loading"
                class="p-1.5 text-gray-400 hover:text-white transition-colors ml-1"
                title="Refresh logs"
            >
                <svg 
                    class="w-5 h-5" 
                    :class="loading ? 'animate-spin text-blue-500' : ''"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            
            <!-- Settings Dropdown (Engrenagem) -->
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button class="p-1.5 text-gray-400 hover:text-white transition-colors" title="Settings">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </button>
                </template>

                <template #content>
                    <div class="text-xs font-semibold px-4 py-2 text-gray-400 uppercase tracking-wider border-b border-gray-600 mb-1">
                        Gerenciamento
                    </div>
                    
                    <button 
                        @click="emit('action', 'clear', { file: filters.type + '.log' })"
                        class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-900/20 hover:text-red-300 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Limpar Log Atual
                    </button>

                    <button 
                        @click="emit('action', 'download', { file: filters.type + '.log' })"
                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download do Arquivo
                    </button>

                    <button 
                        @click="emit('action', 'clean', { days: 30 })"
                        class="w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-700 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Limpar Antigos (>30d)
                    </button>

                    <div class="h-px bg-gray-600 my-1"></div>

                    <button 
                        @click="emit('action', 'test')"
                        class="w-full text-left px-4 py-2 text-sm text-blue-400 hover:bg-blue-900/20 hover:text-blue-300 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Gerar Logs de Teste
                    </button>
                </template>
            </Dropdown>
        </div>
    </div>
</template>
