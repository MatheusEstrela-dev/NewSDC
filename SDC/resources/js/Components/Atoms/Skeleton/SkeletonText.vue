<template>
    <div class="space-y-2">
        <SkeletonBase
            v-for="i in lines"
            :key="i"
            :width="i === lines ? lastLineWidth : '100%'"
            :height="lineHeight"
        />
    </div>
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';

defineProps({
    lines: {
        type: Number,
        default: 3
    },
    lineHeight: {
        type: String,
        default: '0.875rem'
    },
    lastLineWidth: {
        type: String,
        default: '75%'
    }
});
</script>
