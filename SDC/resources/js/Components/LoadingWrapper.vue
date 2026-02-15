<template>
    <div class="loading-wrapper">
        <!-- Skeleton Loading State -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isLoading" class="loading-skeleton">
                <component :is="skeletonComponent" :animation="animation" />
            </div>
        </Transition>

        <!-- Conteúdo Real -->
        <Transition
            enter-active-class="transition-opacity duration-500 delay-100"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="!isLoading" class="content-loaded">
                <slot />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePageLoading } from '@/composables/usePageLoading'
import SkeletonDashboard from '@/Components/Skeleton/SkeletonDashboard.vue'
import SkeletonPae from '@/Components/Skeleton/SkeletonPae.vue'
import SkeletonCard from '@/Components/Skeleton/SkeletonCard.vue'
import SkeletonTable from '@/Components/Skeleton/SkeletonTable.vue'

const props = defineProps({
    // Tipo de skeleton a exibir
    skeleton: {
        type: String,
        default: 'dashboard',
        validator: (value) => ['dashboard', 'pae', 'card', 'table', 'custom'].includes(value)
    },

    // Componente de skeleton customizado
    customSkeleton: {
        type: Object,
        default: null
    },

    // Tipo de animação
    animation: {
        type: String,
        default: 'pulse',
        validator: (value) => ['pulse', 'wave', 'none'].includes(value)
    },

    // Forçar estado de loading (override)
    forceLoading: {
        type: Boolean,
        default: null
    },

    // Tempo mínimo de exibição do skeleton (ms)
    minDisplayTime: {
        type: Number,
        default: 0
    }
})

// Usa estado global de loading ou forceLoading prop
const { isLoading: globalLoading } = usePageLoading()

const isLoading = computed(() => {
    return props.forceLoading !== null ? props.forceLoading : globalLoading.value
})

// Mapeia tipo de skeleton para componente
const skeletonComponent = computed(() => {
    if (props.skeleton === 'custom' && props.customSkeleton) {
        return props.customSkeleton
    }

    const components = {
        dashboard: SkeletonDashboard,
        pae: SkeletonPae,
        card: SkeletonCard,
        table: SkeletonTable
    }

    return components[props.skeleton] || SkeletonDashboard
})
</script>

<style scoped>
.loading-wrapper {
    position: relative;
    min-height: 200px;
}

.loading-skeleton,
.content-loaded {
    width: 100%;
}
</style>
