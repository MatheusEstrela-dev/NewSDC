<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      colorClass
    ]"
  >
    <component :is="iconComponent" v-if="showIcon" class="w-3 h-3 mr-1" />
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import {
  ArrowDownCircleIcon,
  ArrowUpCircleIcon,
  ArrowsRightLeftIcon,
  WrenchIcon,
  ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['entrada', 'saida', 'transferencia', 'ajuste', 'devolucao'].includes(value)
  },
  showIcon: {
    type: Boolean,
    default: true
  }
})

const typeConfig = {
  entrada: {
    label: 'Entrada',
    color: 'bg-green-100 text-green-800',
    icon: ArrowDownCircleIcon
  },
  saida: {
    label: 'Saída',
    color: 'bg-red-100 text-red-800',
    icon: ArrowUpCircleIcon
  },
  transferencia: {
    label: 'Transferência',
    color: 'bg-blue-100 text-blue-800',
    icon: ArrowsRightLeftIcon
  },
  ajuste: {
    label: 'Ajuste',
    color: 'bg-yellow-100 text-yellow-800',
    icon: WrenchIcon
  },
  devolucao: {
    label: 'Devolução',
    color: 'bg-purple-100 text-purple-800',
    icon: ArrowUturnLeftIcon
  }
}

const label = computed(() => typeConfig[props.type]?.label || props.type)
const colorClass = computed(() => typeConfig[props.type]?.color || 'bg-gray-100 text-gray-800')
const iconComponent = computed(() => typeConfig[props.type]?.icon || ArrowsRightLeftIcon)
</script>
