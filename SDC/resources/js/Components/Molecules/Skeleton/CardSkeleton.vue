<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 border border-gray-200 dark:border-gray-700">
        <div v-if="showHeader" class="flex items-center justify-between mb-4">
            <SkeletonBase width="40%" height="1.25rem" />
            <SkeletonBase v-if="showAction" width="80px" height="2rem" rounded="lg" />
        </div>

        <div v-if="showAvatar" class="flex items-center gap-3 mb-4">
            <SkeletonAvatar size="3rem" />
            <div class="flex-1 space-y-2">
                <SkeletonBase width="60%" height="1rem" />
                <SkeletonBase width="40%" height="0.75rem" />
            </div>
        </div>

        <SkeletonText
            v-if="showContent"
            :lines="contentLines"
        />

        <div v-if="showStats" class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div v-for="i in 3" :key="`stat-${i}`" class="text-center">
                <SkeletonBase width="60%" height="1.5rem" custom-class="mx-auto mb-1" />
                <SkeletonBase width="80%" height="0.75rem" custom-class="mx-auto" />
            </div>
        </div>

        <div v-if="showFooter" class="flex gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <SkeletonBase width="100px" height="2.25rem" rounded="lg" />
            <SkeletonBase width="100px" height="2.25rem" rounded="lg" />
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from '@/Components/Atoms/Skeleton/SkeletonBase.vue';
import SkeletonAvatar from '@/Components/Atoms/Skeleton/SkeletonAvatar.vue';
import SkeletonText from '@/Components/Atoms/Skeleton/SkeletonText.vue';

defineProps({
    showHeader: {
        type: Boolean,
        default: true
    },
    showAction: {
        type: Boolean,
        default: false
    },
    showAvatar: {
        type: Boolean,
        default: false
    },
    showContent: {
        type: Boolean,
        default: true
    },
    contentLines: {
        type: Number,
        default: 3
    },
    showStats: {
        type: Boolean,
        default: false
    },
    showFooter: {
        type: Boolean,
        default: false
    }
});
</script>
