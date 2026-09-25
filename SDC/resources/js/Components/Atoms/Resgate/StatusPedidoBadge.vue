<template>
  <Badge :cor="STATUS[status]?.cor ?? 'slate'" size="sm" data-status-pedido>{{ STATUS[status]?.rotulo ?? status }}</Badge>
</template>

<script setup>
/** Status do pedido de resgate; espelha o CHECK de resgate.pedidos. */
import Badge from '@/Components/Atoms/Badge/Badge.vue';

defineProps({
  status: { type: String, required: true },
});

const STATUS = {
  reservado: { rotulo: 'Reservado · aguarda CEDEC', cor: 'amber' },
  aprovado: { rotulo: 'Aprovado', cor: 'emerald' },
  recusado: { rotulo: 'Recusado', cor: 'red' },
  cancelado: { rotulo: 'Cancelado', cor: 'slate' },
  expirado: { rotulo: 'Expirado', cor: 'slate' },
};
</script>
