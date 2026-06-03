<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    size?: number;
    color?: string;
    margin?: string;
    lineHeight?: string | number;
    fontWeight?: string;
    align?: string;
    letterSpacing?: string;
    fontFamily?: string;
}

const props = withDefaults(defineProps<Props>(), {
    size: 15,
    color: '#0B1F3A',
    margin: '0 0 16px',
    lineHeight: '1.5',
    fontWeight: 'normal',
    align: 'left',
    letterSpacing: 'normal',
    fontFamily: 'Arial, Helvetica, sans-serif',
});

const styleObj = computed(() => ({
    margin: props.margin,
    fontSize: `${props.size}px`,
    color: props.color,
    lineHeight: String(props.lineHeight),
    fontWeight: props.fontWeight,
    textAlign: props.align,
    letterSpacing: props.letterSpacing,
    fontFamily: props.fontFamily,
}));
</script>

<template>
    <p :style="styleObj">
        <slot />
    </p>
</template>
