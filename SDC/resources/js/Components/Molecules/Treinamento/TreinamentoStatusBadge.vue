<script setup>
/**
 * Status do treinamento como pill, no padrao do sistema (atomo Badge).
 *
 * Mesmo desenho do StatusBadge do RAT e do ReservaStatusBadge: o mapa
 * status -> variant vive AQUI e nao no enum PHP. O `status_color` que o backend
 * manda (yellow, blue, green...) nao e uma variant do Badge, entao era ignorado
 * e toda pill saia cinza.
 */
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { computed } from 'vue';

const props = defineProps({
  // StatusTreinamento: PLANEJADO | EM_ANDAMENTO | CONCLUIDO | CANCELADO
  status: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
  },
});

// Semantica comum aos modulos: default = ainda nao iniciado, warning = em
// andamento, success = concluido, neutral = encerrado sem desfecho.
const VARIANTS = {
  PLANEJADO: 'default',
  EM_ANDAMENTO: 'warning',
  CONCLUIDO: 'success',
  CANCELADO: 'neutral',
};

const variant = computed(() => VARIANTS[props.status] ?? 'default');
</script>

<template>
  <Badge v-if="status" :variant="variant" :size="size">
    {{ label || status }}
  </Badge>
</template>
