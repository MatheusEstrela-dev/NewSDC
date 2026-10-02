<template>
  <Badge :variant="VARIANTE[status] ?? 'default'" size="sm">{{ label }}</Badge>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  status: { type: String, required: true },
  label: { type: String, required: true },
});

// Lote: ativo/desfeito. Item e emprestimo avulso: ativo/devolvido/substituida.
// Semantica do Badge: ativo = vigente; desfeito = lote revertido, deixou de
// valer (inativo, danger); devolvido = ciclo concluido que so informa, sem
// competir com o verde do ativo (info); substituida = trocada por outro item.
const VARIANTE = { ativo: 'success', desfeito: 'danger', devolvido: 'info', substituida: 'warning' };
</script>
