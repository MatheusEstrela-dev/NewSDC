<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

/*
 * Situacao do certificado: valido, reemitido ou cancelado. Cancelado vence
 * reemitido, porque um certificado reemitido que depois foi cancelado nao vale.
 */
const props = defineProps({
  certificado: { type: Object, required: true },
});

const situacao = computed(() => {
  const { cancelado, reemitido, disponivel, status_label: rotulo } = props.certificado;
  if (cancelado) return { texto: 'Cancelado', variant: 'danger' };
  if (disponivel && reemitido) return { texto: 'Reemitido', variant: 'info' };
  if (disponivel) return { texto: 'Válido', variant: 'success' };
  return { texto: rotulo, variant: 'warning' };
});
</script>

<template>
  <Badge :variant="situacao.variant">{{ situacao.texto }}</Badge>
</template>
