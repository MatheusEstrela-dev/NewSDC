<template>
  <Badge :variant="varianteStatusAcesso(status)" size="sm">{{ rotuloStatusAcesso(status) }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { rotuloStatusAcesso, varianteStatusAcesso } from '@/Support/acessos';

defineProps({
  status: { type: String, default: '' },
});
</script>
