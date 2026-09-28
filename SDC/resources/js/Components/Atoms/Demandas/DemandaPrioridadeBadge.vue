<template>
  <Badge :cor="COR[prioridade] ?? 'slate'" size="sm" :title="itil ? `Prioridade ITIL: ${itil}` : undefined">
    {{ label }}
  </Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  prioridade: { type: String, required: true },
  label: { type: String, required: true },
  itil: { type: String, default: null },
});

const COR = { baixa: 'sky', media: 'amber', alta: 'orange' };
</script>
