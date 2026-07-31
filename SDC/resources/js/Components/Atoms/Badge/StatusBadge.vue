<template>
  <Badge :variant="statusVariant" :size="size" :rounded="rounded">
    <slot>{{ statusLabel }}</slot>
  </Badge>
</template>

<script setup>
import { computed } from 'vue';
import Badge from './Badge.vue';

const props = defineProps({
  status: {
    type: String,
    required: true,
    validator: (value) => ['rascunho', 'em_andamento', 'finalizado', 'arquivado'].includes(value),
  },
  size: {
    type: String,
    default: 'md',
  },
  rounded: {
    type: Boolean,
    default: true,
  },
});

// Semantica de cor comum aos modulos (Decretacoes, Compdec, PAE):
//   emerald/success = concluido, ativo
//   amber/warning   = em andamento, aguardando, em analise
//   red/danger      = vencido, inativo, negado
//   slate/default   = neutro, ainda nao iniciado
//   slate/neutral   = encerrado sem desfecho (arquivado)
//
// em_andamento era 'info' (azul), destoando dos demais modulos, onde azul e
// informativo e nao progresso. Com ele em amber, 'rascunho' saiu de amber para
// nao virar a mesma cor: rascunho e ausencia de progresso, nao progresso.
const statusConfig = {
  rascunho: {
    label: 'Rascunho',
    variant: 'default',
  },
  em_andamento: {
    label: 'Em Andamento',
    variant: 'warning',
  },
  finalizado: {
    label: 'Finalizado',
    variant: 'success',
  },
  arquivado: {
    label: 'Arquivado',
    variant: 'neutral',
  },
};

const statusLabel = computed(() => {
  return statusConfig[props.status]?.label || props.status;
});

const statusVariant = computed(() => {
  return statusConfig[props.status]?.variant || 'default';
});
</script>

