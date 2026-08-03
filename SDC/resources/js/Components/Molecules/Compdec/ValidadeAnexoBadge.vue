<template>
  <Badge :variant="variant" size="pill" class="whitespace-nowrap" :class="{ 'opacity-60': !status }">
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Validade do anexo da COMPDEC.
 *
 * Estado, e nao categoria: semantica do Badge em vez de cor explicita. A receita de
 * pill vinha escrita a mao aqui.
 */
import { computed } from 'vue';
import Badge from '../../Atoms/Badge/Badge.vue';

const props = defineProps({
  status: {
    type: String,
    required: false,
    default: null,
  },
});

const config = {
  vigente: { label: 'Vigente', variant: 'success' },
  prox_vencimento: { label: 'Prox. Vencimento', variant: 'warning' },
  vencido: { label: 'Vencido', variant: 'danger' },
  sem_validade: { label: 'Sem Validade', variant: 'default' },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return config[props.status]?.label || props.status;
});

const variant = computed(() => config[props.status]?.variant ?? 'default');
</script>
