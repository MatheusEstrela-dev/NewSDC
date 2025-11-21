<template>
    <div class="skeleton-table bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <!-- Table Header -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <div class="grid gap-4 p-4" :style="gridStyle">
                <SkeletonBase
                    v-for="i in columns"
                    :key="`header-${i}`"
                    shape="line"
                    width="80%"
                    height="20px"
                    rounded="md"
                    :animation="animation"
                />
            </div>
        </div>

        <!-- Table Rows -->
        <div>
            <div
                v-for="row in rows"
                :key="`row-${row}`"
                class="border-b border-gray-100 dark:border-gray-700 last:border-0"
            >
                <div class="grid gap-4 p-4" :style="gridStyle">
                    <SkeletonBase
                        v-for="col in columns"
                        :key="`row-${row}-col-${col}`"
                        shape="line"
                        :width="getCellWidth(col)"
                        height="16px"
                        rounded="md"
                        :animation="animation"
                    />
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="showPagination" class="border-t border-gray-200 dark:border-gray-700 p-4">
            <div class="flex items-center justify-between">
                <SkeletonBase
                    shape="line"
                    width="120px"
                    height="20px"
                    rounded="md"
                    :animation="animation"
                />
                <div class="flex gap-2">
                    <SkeletonBase
                        v-for="i in 3"
                        :key="`pagination-${i}`"
                        shape="rectangle"
                        width="36px"
                        height="36px"
                        rounded="md"
                        :animation="animation"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import SkeletonBase from './SkeletonBase.vue'

const props = defineProps({
    // Número de colunas
    columns: {
        type: Number,
        default: 4
    },

    // Número de linhas
    rows: {
        type: Number,
        default: 5
    },

    // Mostrar paginação
    showPagination: {
        type: Boolean,
        default: true
    },

    // Tipo de animação
    animation: {
        type: String,
        default: 'pulse'
    }
})

// Grid style dinâmico
const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${props.columns}, 1fr)`
}))

// Largura variável das células
const getCellWidth = (index) => {
    const widths = ['90%', '85%', '95%', '80%', '100%']
    return widths[index % widths.length]
}
</script>
