<template>
    <div class="grid gap-4" :class="gridClass">
        <div
            v-for="i in cards"
            :key="`stat-${i}`"
            class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 border border-gray-200 dark:border-gray-700"
        >
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <SkeletonBase width="60%" height="0.75rem" custom-class="mb-2" />
                    <SkeletonBase width="40%" height="1.5rem" />
                </div>
                <SkeletonBase width="2.5rem" height="2.5rem" rounded="lg" />
            </div>
            <div v-if="showTrend" class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                <SkeletonBase width="50%" height="0.75rem" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import SkeletonBase from '@/Components/Atoms/Skeleton/SkeletonBase.vue';

const props = defineProps({
    cards: {
        type: Number,
        default: 4
    },
    columns: {
        type: Number,
        default: 4
    },
    showTrend: {
        type: Boolean,
        default: false
    }
});

const gridClass = computed(() => {
    const colsMap = {
        1: 'grid-cols-1',
        2: 'grid-cols-1 sm:grid-cols-2',
        3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
        4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
    };
    return colsMap[props.columns] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
});
</script>
