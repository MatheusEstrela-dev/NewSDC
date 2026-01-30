<template>
    <SkeletonBase
        :width="size"
        :height="size"
        rounded="full"
    />
</template>

<script setup>
import SkeletonBase from './SkeletonBase.vue';

defineProps({
    size: {
        type: String,
        default: '2.5rem'
    }
});
</script>
