<template>
  <Badge :variant="statusVariant" :size="size" :rounded="rounded">
    <slot>{{ statusLabel }}</slot>
  </Badge>
</template>

<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['rascunho', 'em_andamento', 'finalizado', 'arquivado'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
  },
  rounded: {
    type: Boolean,
    default: true,
  },
});

const statusConfig = {
  rascunho: {
    label: 'Rascunho',
    variant: 'warning',
  },
  em_andamento: {
    label: 'Em Andamento',
    variant: 'info',
  },
  finalizado: {
    label: 'Finalizado',
    variant: 'success',
  },
  arquivado: {
    label: 'Arquivado',
    variant: 'default',
  },
};

const statusLabel = computed(() => {
  return statusConfig[props.status]?.label || props.status;
});

const statusVariant = computed(() => {
  return statusConfig[props.status]?.variant || 'default';
});
</script>

