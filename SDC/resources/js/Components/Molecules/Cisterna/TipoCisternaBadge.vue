<template>
  <Badge :variant="variant" :size="size">
    {{ label }}
  </Badge>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  tipo: { type: String, required: false, default: null },
  size: { type: String, default: 'md' },
});

const config = {
  comunitaria: { label: 'Comunitaria', variant: 'info' },
  individual: { label: 'Individual', variant: 'success' },
  escolar: { label: 'Escolar', variant: 'warning' },
};

const label = computed(() => {
  if (!props.tipo) return 'N/A';
  return config[props.tipo]?.label || props.tipo;
});

const variant = computed(() => {
  return config[props.tipo]?.variant || 'default';
});
</script>
