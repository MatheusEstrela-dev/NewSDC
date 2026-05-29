<script setup lang="ts">
interface Props {
    text: string;
}

defineProps<Props>();
</script>

<template>
    <h1
        :style="{
            margin: '0 0 16px',
            fontSize: '20px',
            color: '#0B1F3A',
            fontFamily: 'Arial, Helvetica, sans-serif',
        }"
    >
        {{ text }}
    </h1>
</template>
