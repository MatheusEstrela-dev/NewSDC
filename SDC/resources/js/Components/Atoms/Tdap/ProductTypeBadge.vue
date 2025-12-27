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
  ShoppingCartIcon,
  BeakerIcon,
  RectangleStackIcon,
  CubeIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['cesta_basica', 'kit_limpeza', 'colchao', 'outros'].includes(value)
  },
  showIcon: {
    type: Boolean,
    default: true
  }
})

const typeConfig = {
  cesta_basica: {
    label: 'Cesta Básica',
    color: 'bg-green-100 text-green-800',
    icon: ShoppingCartIcon
  },
  kit_limpeza: {
    label: 'Kit Limpeza',
    color: 'bg-blue-100 text-blue-800',
    icon: BeakerIcon
  },
  colchao: {
    label: 'Colchão',
    color: 'bg-purple-100 text-purple-800',
    icon: RectangleStackIcon
  },
  outros: {
    label: 'Outros',
    color: 'bg-gray-100 text-gray-800',
    icon: CubeIcon
  }
}

const label = computed(() => typeConfig[props.type]?.label || props.type)
const colorClass = computed(() => typeConfig[props.type]?.color || 'bg-gray-100 text-gray-800')
const iconComponent = computed(() => typeConfig[props.type]?.icon || CubeIcon)
</script>
