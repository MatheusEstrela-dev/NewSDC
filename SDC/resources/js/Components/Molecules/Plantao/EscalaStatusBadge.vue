<script setup>
/**
 * Situacao da escala como pill.
 *
 * Consome o atomo Badge em vez de montar o proprio `<span class="rounded-full
 * ...">`: o Badge e a fonte unica da aparencia de pill do sistema, e um span
 * proprio aqui seria a 11a copia da mesma receita — sem a borda que da o
 * contorno no tema claro.
 *
 * O mapa status -> variant vive AQUI e nao no enum PHP: o Tailwind nao escaneia
 * `app/**\/*.php`, entao qualquer classe derivada de valor vindo do backend seria
 * purgada do bundle. O backend manda o valor cru (`status_valor`); a aparencia e
 * decidida no frontend.
 */
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { computed } from 'vue';

const props = defineProps({
  // StatusEscala: RASCUNHO | PUBLICADA | ARQUIVADA
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
    case 'PUBLICADA':
      return 'success';
    case 'ARQUIVADA':
      return 'neutral';
    // Rascunho e estado de trabalho, nao erro: warning comunica "ainda nao
    // esta valendo" sem parecer falha.
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
