<template>
  <!-- rounded (e nao rounded-full) e size sm: esta pill e menor que as de status,
       porque aparece encostada na data dentro da celula. -->
  <span v-if="prazoLabel || estimado" class="inline-flex flex-wrap items-center gap-1" :class="props.class">
    <Badge v-if="prazoLabel" :variant="variant" size="sm" :rounded="false">
      {{ prazoLabel }}
    </Badge>
    <Badge
      v-if="estimado"
      variant="neutral"
      size="sm"
      :rounded="false"
      title="Data da notificacao da FEAM estimada pela data de entrada. Informe a data real no historico do protocolo."
    >
      Estimado
    </Badge>
  </span>
</template>

<script setup>
/**
 * Situacao do prazo de analise do protocolo PAE (Art. 9), calculada no servidor.
 * "Estimado" marca a data da FEAM inferida no backfill.
 */
import { computed } from 'vue';
import Badge from '../../../Atoms/Badge/Badge.vue';

const props = defineProps({
  prazo: {
    type: String,
    default: 'ok', // ok|proximo|vencido|pausado|sem_data
  },
  estimado: {
    type: Boolean,
    default: false,
  },
  class: {
    type: String,
    default: '',
  },
});

const map = {
  proximo: { label: 'Próximo', variant: 'warning' },
  vencido: { label: 'Vencido', variant: 'danger' },
  pausado: { label: 'Pausado', variant: 'info' },
  sem_data: { label: 'Sem data FEAM', variant: 'neutral' },
};

const prazoLabel = computed(() => map[props.prazo]?.label || '');
const variant = computed(() => map[props.prazo]?.variant ?? 'default');
</script>
