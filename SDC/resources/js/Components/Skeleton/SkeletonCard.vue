<template>
    <div class="skeleton-card bg-white dark:bg-gray-800 rounded-lg p-6 shadow-md">
        <!-- Header -->
        <div v-if="showHeader" class="mb-4 flex items-center justify-between">
            <div class="flex-1">
                <SkeletonBase
                    shape="line"
                    :width="headerWidth"
                    height="24px"
                    rounded="md"
                    :animation="animation"
                />
            </div>
            <SkeletonBase
                v-if="showHeaderAction"
                shape="rectangle"
                width="80px"
                height="32px"
                rounded="md"
                :animation="animation"
            />
        </div>

        <!-- Content Lines -->
        <div v-if="lines > 0" class="space-y-3">
            <SkeletonBase
                v-for="i in lines"
                :key="i"
                shape="line"
                :width="getLineWidth(i)"
                height="16px"
                rounded="md"
                :animation="animation"
            />
        </div>

        <!-- Image/Media -->
        <div v-if="showImage" :class="{ 'mt-4': lines > 0 }">
            <SkeletonBase
                shape="rectangle"
                :width="imageWidth"
                :height="imageHeight"
                rounded="lg"
                :animation="animation"
            />
        </div>

        <!-- Footer -->
        <div v-if="showFooter" class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <SkeletonBase
                    v-for="i in footerItems"
                    :key="i"
                    shape="rectangle"
                    width="60px"
                    height="24px"
                    rounded="full"
                    :animation="animation"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import SkeletonBase from './SkeletonBase.vue'

const props = defineProps({
    // Número de linhas de texto
    lines: {
        type: Number,
        default: 3
    },

    // Mostrar header
    showHeader: {
        type: Boolean,
        default: true
    },

    // Largura do header
    headerWidth: {
        type: String,
        default: '60%'
    },

    // Mostrar ação no header
    showHeaderAction: {
        type: Boolean,
        default: false
    },

    // Mostrar imagem
    showImage: {
        type: Boolean,
        default: false
    },

    // Largura da imagem
    imageWidth: {
        type: String,
        default: '100%'
    },

    // Altura da imagem
    imageHeight: {
        type: String,
        default: '200px'
    },

    // Mostrar footer
    showFooter: {
        type: Boolean,
        default: false
    },

    // Número de items no footer
    footerItems: {
        type: Number,
        default: 2
    },

    // Tipo de animação
    animation: {
        type: String,
        default: 'pulse'
    }
})

// Função para variar largura das linhas
const getLineWidth = (index) => {
    const widths = ['100%', '95%', '90%', '85%', '75%']
    return widths[index % widths.length] || '80%'
}
</script>
