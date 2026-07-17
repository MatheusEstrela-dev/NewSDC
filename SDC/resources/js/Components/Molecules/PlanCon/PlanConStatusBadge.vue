<template>
  <Badge :variant="variant" size="sm">{{ label }}</Badge>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  situacao: { type: String, default: null },
});

const normalizada = computed(() => props.situacao?.toLowerCase() ?? '');
const variant = computed(() => normalizada.value === 'regular' ? 'success' : 'warning');
const label = computed(() => normalizada.value === 'regular' ? 'Regular' : 'Irregular');
</script>