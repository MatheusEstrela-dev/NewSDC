<template>
    <div
        :class="[
            'animate-pulse bg-gray-200 dark:bg-gray-700 rounded',
            customClass
        ]"
        :style="computedStyle"
    />
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    width: {
        type: String,
        default: '100%'
    },
    height: {
        type: String,
        default: '1rem'
    },
    rounded: {
        type: String,
        default: 'md',
        validator: (value) => ['none', 'sm', 'md', 'lg', 'full'].includes(value)
    },
    customClass: {
        type: String,
        default: ''
    }
});

const computedStyle = computed(() => ({
    width: props.width,
    height: props.height
}));
</script>
