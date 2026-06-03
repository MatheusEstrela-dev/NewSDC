<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    href: string;
    label: string;
    bg?: string;
    color?: string;
}

const props = withDefaults(defineProps<Props>(), {
    bg: '#DC2626',
    color: '#FFFFFF',
});

const styleObj = computed(() => ({
    display: 'inline-block',
    background: props.bg,
    color: props.color,
    padding: '8px 16px',
    borderRadius: '4px',
    textDecoration: 'none',
    fontSize: '13px',
    fontWeight: 'bold',
    fontFamily: 'Arial, Helvetica, sans-serif',
}));
</script>

<template>
    <a :href="href" :style="styleObj">{{ label }}</a>
</template>
