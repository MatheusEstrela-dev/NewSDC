<template>
  <Badge :variant="variant" :size="size">
    {{ label }}
  </Badge>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  status: { type: String, required: false, default: null },
  size: { type: String, default: 'md' },
});

const config = {
  ativa: { label: 'Ativa', variant: 'success' },
  pendente: { label: 'Pendente', variant: 'warning' },
  inativa: { label: 'Inativa', variant: 'default' },
  em_obras: { label: 'Em obras', variant: 'info' },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return config[props.status]?.label || props.status;
});

const variant = computed(() => {
  return config[props.status]?.variant || 'default';
});
</script>
