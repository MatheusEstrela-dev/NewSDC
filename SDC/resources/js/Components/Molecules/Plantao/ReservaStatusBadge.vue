<script setup>
/**
 * Situacao da reserva como pill.
 *
 * Mesmo desenho do EscalaStatusBadge: o mapa status -> variant vive AQUI e nao
 * no enum PHP, porque o Tailwind nao escaneia `app/**\/*.php` e qualquer classe
 * derivada de valor vindo do backend seria purgada do bundle. O backend manda o
 * valor cru (`status_valor`); a aparencia e decidida no frontend.
 */
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { computed } from 'vue';

const props = defineProps({
  // StatusReserva: AGENDADA | EM_USO | CONCLUIDA | CANCELADA | EXPIRADA
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

const variant = computed(() => {
  switch (props.status) {
    // Chave na mao de alguem e viatura rodando: o estado que o plantao precisa
    // enxergar primeiro na lista.
    case 'EM_USO':
      return 'info';
    case 'CONCLUIDA':
      return 'success';
    // Expirada e o unico estado que denuncia um problema operacional (reserva
    // que bloqueou a viatura e ninguem retirou), entao vem em vermelho.
    case 'EXPIRADA':
      return 'danger';
    case 'CANCELADA':
      return 'neutral';
    default:
      return 'warning';
  }
});
</script>

<template>
  <Badge v-if="status" :variant="variant" :size="size">
    {{ label || status }}
  </Badge>
</template>
