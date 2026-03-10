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
    <div class="bg-[#0d1117] border-b border-gray-800/50 flex flex-col">
        <!-- Header Row -->
        <div class="px-5 py-3 flex items-center justify-between">
            <h2 class="text-lg font-medium text-gray-200">Selecione um arquivo</h2>

            <!-- Stats Badges -->
            <div class="flex items-center gap-2">
                <button
                    @click="toggleLevel('debug')"
                    class="px-3 py-1 rounded text-xs font-medium transition-all"
                    :class="filters.level === 'debug'
                        ? 'bg-gray-600 text-white'
                        : 'bg-gray-800 text-gray-400 hover:bg-gray-700'"
                >
                    {{ counts.debug }} Debug
                </button>

                <button
                    @click="toggleLevel('info')"
                    class="px-3 py-1 rounded text-xs font-medium transition-all"
                    :class="filters.level === 'info'
                        ? 'bg-blue-600 text-white'
                        : 'bg-blue-900/30 text-blue-400 hover:bg-blue-900/50'"
                >
                    {{ counts.info }} Info
                </button>

                <button
                    @click="toggleLevel('warning')"
                    class="px-3 py-1 rounded text-xs font-medium transition-all"
                    :class="filters.level === 'warning'
                        ? 'bg-yellow-600 text-white'
                        : 'bg-yellow-900/30 text-yellow-400 hover:bg-yellow-900/50'"
                >
                    {{ counts.warning }} Warning
                </button>

                <button
                    @click="toggleLevel('error')"
                    class="px-3 py-1 rounded text-xs font-medium transition-all"
                    :class="filters.level === 'error'
                        ? 'bg-red-600 text-white'
                        : 'bg-red-900/30 text-red-400 hover:bg-red-900/50'"
                >
                    {{ counts.error }} Error
                </button>

                <button
                    @click="emit('refresh')"
                    :disabled="loading"
                    class="ml-2 p-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
                    title="Atualizar logs"
                >
                    <svg
                        class="w-4 h-4"
                        :class="loading ? 'animate-spin text-blue-400' : ''"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- Search Row -->
        <div class="px-5 pb-3">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input
                    :value="filters.search"
                    @input="e => emit('update:filters', { ...filters, search: e.target.value })"
                    type="text"
                    placeholder="Buscar nos logs..."
                    class="block w-full pl-10 pr-3 py-2 bg-[#161b22] border border-gray-700/50 rounded-lg text-sm text-gray-200 placeholder-gray-500 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                />
            </div>
        </div>
    </div>
</template>
