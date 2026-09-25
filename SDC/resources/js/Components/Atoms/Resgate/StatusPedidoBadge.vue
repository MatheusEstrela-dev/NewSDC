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
  aprovado: { rotulo: 'Aprovado · aguarda termo', cor: 'sky' },
  termo_emitido: { rotulo: 'Termo emitido · aguarda assinaturas', cor: 'sky' },
  termo_assinado: { rotulo: 'Termo assinado · aguarda entrega', cor: 'violet' },
  entregue: { rotulo: 'Entregue · aguarda confirmação', cor: 'violet' },
  contestado: { rotulo: 'Entrega contestada', cor: 'orange' },
  concluido: { rotulo: 'Concluído', cor: 'emerald' },
  recusado: { rotulo: 'Recusado', cor: 'red' },
  cancelado: { rotulo: 'Cancelado', cor: 'slate' },
  expirado: { rotulo: 'Expirado', cor: 'slate' },
};
</script>
