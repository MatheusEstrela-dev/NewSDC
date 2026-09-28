<template>
  <Badge :cor="COR[etapa] ?? 'slate'" size="sm">{{ label }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  etapa: { type: String, required: true },
  label: { type: String, required: true },
});

// Mesmas cores das capturas do legado: aberto em vermelho, concluido em verde.
const COR = { aberto: 'red', em_andamento: 'amber', concluido: 'emerald', cancelado: 'slate' };
</script>
