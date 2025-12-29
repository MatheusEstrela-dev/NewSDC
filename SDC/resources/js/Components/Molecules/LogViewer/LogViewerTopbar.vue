<script setup>
import { computed } from 'vue'

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    filters: {
        type: Object,
        required: true
    },
    loading: Boolean
})

const emit = defineEmits(['update:filters', 'refresh'])

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
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold transition-all border"
                :class="filters.level === 'debug' 
                    ? 'bg-gray-700 border-gray-600 text-white ring-1 ring-gray-500' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                Debug: {{ counts.debug.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('info')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold transition-all border"
                :class="filters.level === 'info' 
                    ? 'bg-blue-900/40 border-blue-800 text-blue-100 ring-1 ring-blue-700' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                Info: {{ counts.info.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('warning')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold transition-all border"
                :class="filters.level === 'warning' 
                    ? 'bg-yellow-900/40 border-yellow-800 text-yellow-100 ring-1 ring-yellow-700' 
                    : 'bg-gray-800/50 border-gray-700 text-gray-400 hover:border-gray-600'"
            >
                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                Warning: {{ counts.warning.toLocaleString() }}
            </button>
            
            <button 
                @click="toggleLevel('error')"
                class="flex items-center gap-2 px-3 py-1.5 rounded-md text-xs font-semibold transition-all border"
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
                    class="block w-full pl-10 pr-3 py-1.5 bg-gray-800 border-gray-700 rounded-md text-sm text-gray-200 placeholder-gray-500 focus:ring-blue-500 focus:border-blue-500 transition-colors border"
                />
            </div>
            
            <button 
                @click="emit('refresh')"
                :disabled="loading"
                class="p-1.5 text-gray-400 hover:text-white transition-colors"
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
            
            <button class="p-1.5 text-gray-400 hover:text-white transition-colors" title="Settings">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </button>
        </div>
    </div>
</template>
