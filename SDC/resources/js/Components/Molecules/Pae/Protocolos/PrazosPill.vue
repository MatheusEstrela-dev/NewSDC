<template>
  <!-- rounded (e nao rounded-full) e size sm: esta pill e menor que as de status,
       porque aparece encostada na data dentro da celula. -->
  <Badge
    v-if="prazoLabel"
    :variant="variant"
    size="sm"
    :rounded="false"
    :class="props.class"
  >
    {{ prazoLabel }}
  </Badge>
</template>

<script setup>
/**
 * Aviso de prazo do protocolo PAE: proximo do vencimento ou vencido.
 *
 * Estado puro, entao usa a semantica do Badge. A receita de pill vinha escrita a mao
 * aqui. O texto era text-[11px] e passa a 12px (text-xs do size sm): a diferenca de
 * um pixel nao justifica um token de tamanho exclusivo para um componente.
 */
import { computed } from 'vue';
import Badge from '../../../Atoms/Badge/Badge.vue';

const props = defineProps({
  prazo: {
    type: String,
    default: 'ok', // ok|proximo|vencido
  },
  class: {
    type: String,
    default: '',
  },
});

const map = {
  proximo: { label: 'Próximo', variant: 'warning' },
  vencido: { label: 'Vencido', variant: 'danger' },
};

const prazoLabel = computed(() => map[props.prazo]?.label || '');
const variant = computed(() => map[props.prazo]?.variant ?? 'default');
</script>
