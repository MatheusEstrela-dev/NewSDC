<template>
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex gap-4">
                <SkeletonBase
                    v-for="i in columns"
                    :key="`header-${i}`"
                    :width="getColumnWidth(i)"
                    height="1rem"
                />
            </div>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            <div
                v-for="row in rows"
                :key="`row-${row}`"
                class="px-4 py-4 bg-white dark:bg-gray-900"
            >
                <div class="flex gap-4 items-center">
                    <SkeletonBase
                        v-for="col in columns"
                        :key="`row-${row}-col-${col}`"
                        :width="getColumnWidth(col)"
                        height="0.875rem"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from '@/Components/Atoms/Skeleton/SkeletonBase.vue';

const props = defineProps({
    rows: {
        type: Number,
        default: 5
    },
    columns: {
        type: Number,
        default: 4
    },
    columnWidths: {
        type: Array,
        default: () => []
    }
});

const getColumnWidth = (index) => {
    if (props.columnWidths.length > 0 && props.columnWidths[index - 1]) {
        return props.columnWidths[index - 1];
    }
    const defaultWidths = ['15%', '25%', '20%', '15%', '10%', '15%'];
    return defaultWidths[(index - 1) % defaultWidths.length];
};
</script>
