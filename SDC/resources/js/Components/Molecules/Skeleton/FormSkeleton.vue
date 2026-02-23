<template>
    <div class="space-y-6">
        <div v-for="i in fields" :key="`field-${i}`">
            <SkeletonBase width="30%" height="0.875rem" custom-class="mb-2" />
            <SkeletonBase width="100%" height="2.5rem" rounded="lg" />
        </div>

        <div v-if="showActions" class="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <SkeletonBase width="120px" height="2.5rem" rounded="lg" />
            <SkeletonBase width="100px" height="2.5rem" rounded="lg" />
        </div>
    </div>
</template>

<script setup>
import SkeletonBase from '@/Components/Atoms/Skeleton/SkeletonBase.vue';

defineProps({
    fields: {
        type: Number,
        default: 4
    },
    showActions: {
        type: Boolean,
        default: true
    }
});
</script>
