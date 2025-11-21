<template>
    <div
        class="skeleton-base"
        :class="[
            shapeClass,
            animationClass,
            sizeClass,
            roundedClass,
            customClass
        ]"
        :style="customStyle"
    />
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    // Tipo de forma
    shape: {
        type: String,
        default: 'rectangle',
        validator: (value) => ['rectangle', 'circle', 'line'].includes(value)
    },

    // Tipo de animação
    animation: {
        type: String,
        default: 'pulse',
        validator: (value) => ['pulse', 'wave', 'none'].includes(value)
    },

    // Tamanho predefinido
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl', 'custom'].includes(value)
    },

    // Largura customizada
    width: {
        type: String,
        default: null
    },

    // Altura customizada
    height: {
        type: String,
        default: null
    },

    // Bordas arredondadas
    rounded: {
        type: String,
        default: 'md',
        validator: (value) => ['none', 'sm', 'md', 'lg', 'full'].includes(value)
    },

    // Classes customizadas
    customClass: {
        type: String,
        default: ''
    }
})

// Classes computadas
const shapeClass = computed(() => {
    const shapes = {
        rectangle: 'skeleton-rectangle',
        circle: 'skeleton-circle',
        line: 'skeleton-line'
    }
    return shapes[props.shape]
})

const animationClass = computed(() => {
    const animations = {
        pulse: 'skeleton-pulse',
        wave: 'skeleton-wave',
        none: ''
    }
    return animations[props.animation]
})

const sizeClass = computed(() => {
    if (props.size === 'custom') return ''

    const sizes = {
        xs: 'skeleton-xs',
        sm: 'skeleton-sm',
        md: 'skeleton-md',
        lg: 'skeleton-lg',
        xl: 'skeleton-xl'
    }
    return sizes[props.size]
})

const roundedClass = computed(() => {
    const rounded = {
        none: 'rounded-none',
        sm: 'rounded-sm',
        md: 'rounded-md',
        lg: 'rounded-lg',
        full: 'rounded-full'
    }
    return rounded[props.rounded]
})

const customStyle = computed(() => {
    const style = {}

    if (props.width) {
        style.width = props.width
    }

    if (props.height) {
        style.height = props.height
    }

    return style
})
</script>

<style scoped>
/* Base */
.skeleton-base {
    @apply bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200;
    @apply dark:from-gray-700 dark:via-gray-600 dark:to-gray-700;
}

/* Formas */
.skeleton-rectangle {
    @apply block;
}

.skeleton-circle {
    @apply block;
    aspect-ratio: 1;
}

.skeleton-line {
    @apply block h-4;
}

/* Tamanhos padrões */
.skeleton-xs {
    @apply h-8 w-full;
}

.skeleton-sm {
    @apply h-16 w-full;
}

.skeleton-md {
    @apply h-24 w-full;
}

.skeleton-lg {
    @apply h-32 w-full;
}

.skeleton-xl {
    @apply h-48 w-full;
}

/* Animações */
@keyframes skeleton-pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}

@keyframes skeleton-wave {
    0% {
        background-position: -200% 0;
    }
    100% {
        background-position: 200% 0;
    }
}

.skeleton-pulse {
    animation: skeleton-pulse 1.5s ease-in-out infinite;
}

.skeleton-wave {
    background-size: 200% 100%;
    animation: skeleton-wave 1.5s linear infinite;
}
</style>
