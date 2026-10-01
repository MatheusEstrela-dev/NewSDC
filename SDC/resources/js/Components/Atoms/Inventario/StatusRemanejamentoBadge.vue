<template>
  <Badge :variant="VARIANTE[status] ?? 'default'" size="sm">{{ label }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  status: { type: String, required: true },
  label: { type: String, required: true },
});

// Lote: ativo/desfeito. Item: ativo/devolvido/substituida.
const VARIANTE = { ativo: 'success', desfeito: 'neutral', devolvido: 'default', substituida: 'warning' };
</script>
