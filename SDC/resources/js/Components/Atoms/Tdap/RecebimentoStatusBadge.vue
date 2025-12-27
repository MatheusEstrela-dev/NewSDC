<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      colorClass
    ]"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['pendente', 'em_conferencia', 'conferido', 'aprovado', 'rejeitado', 'finalizado'].includes(value)
  }
})

const statusConfig = {
  pendente: {
    label: 'Pendente',
    color: 'bg-gray-100 text-gray-800'
  },
  em_conferencia: {
    label: 'Em Conferência',
    color: 'bg-blue-100 text-blue-800'
  },
  conferido: {
    label: 'Conferido',
    color: 'bg-yellow-100 text-yellow-800'
  },
  aprovado: {
    label: 'Aprovado',
    color: 'bg-green-100 text-green-800'
  },
  rejeitado: {
    label: 'Rejeitado',
    color: 'bg-red-100 text-red-800'
  },
  finalizado: {
    label: 'Finalizado',
    color: 'bg-purple-100 text-purple-800'
  }
}

const label = computed(() => statusConfig[props.status]?.label || props.status)
const colorClass = computed(() => statusConfig[props.status]?.color || 'bg-gray-100 text-gray-800')
</script>
