<template>
  <div class="flex items-center space-x-2">
    <div class="flex-1">
      <div class="flex items-center justify-between text-xs mb-1">
        <span class="font-medium">{{ quantidadeAtual }}</span>
        <span class="text-gray-500">de {{ quantidadeMaxima || '∞' }}</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-2">
        <div
          :class="[
            'h-2 rounded-full transition-all duration-300',
            barColorClass
          ]"
          :style="{ width: `${percentual}%` }"
        />
      </div>
    </div>
    <component
      :is="alertIcon"
      v-if="showAlert"
      :class="['w-5 h-5', alertColorClass]"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ExclamationTriangleIcon,
  CheckCircleIcon,
  XCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  quantidadeAtual: {
    type: Number,
    required: true
  },
  quantidadeMinima: {
    type: Number,
    default: 0
  },
  quantidadeMaxima: {
    type: Number,
    default: null
  }
})

const percentual = computed(() => {
  if (!props.quantidadeMaxima) return 50 // Sem máximo definido
  return Math.min((props.quantidadeAtual / props.quantidadeMaxima) * 100, 100)
})

const status = computed(() => {
  if (props.quantidadeAtual === 0) return 'vazio'
  if (props.quantidadeAtual <= props.quantidadeMinima) return 'baixo'
  if (props.quantidadeMaxima && props.quantidadeAtual >= props.quantidadeMaxima) return 'cheio'
  return 'normal'
})

const barColorClass = computed(() => {
  switch (status.value) {
    case 'vazio':
      return 'bg-red-500'
    case 'baixo':
      return 'bg-yellow-500'
    case 'cheio':
      return 'bg-blue-500'
    default:
      return 'bg-green-500'
  }
})

const showAlert = computed(() => ['vazio', 'baixo'].includes(status.value))

const alertIcon = computed(() => {
  if (status.value === 'vazio') return XCircleIcon
  if (status.value === 'baixo') return ExclamationTriangleIcon
  return CheckCircleIcon
})

const alertColorClass = computed(() => {
  if (status.value === 'vazio') return 'text-red-500'
  if (status.value === 'baixo') return 'text-yellow-500'
  return 'text-green-500'
})
</script>
