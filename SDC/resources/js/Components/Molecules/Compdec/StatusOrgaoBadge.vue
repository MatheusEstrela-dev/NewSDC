<template>
  <Badge :variant="variant" size="pill" class="whitespace-nowrap" :class="{ 'opacity-60': !status }">
    {{ label }}
  </Badge>
</template>

<script setup>
/**
 * Situacao do orgao de defesa civil.
 *
 * Estado, e nao categoria: usa a semantica do Badge (success/warning/danger/default)
 * em vez de cor explicita. A receita de pill vinha escrita a mao aqui.
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
  ativo: { label: 'Ativo', variant: 'success' },
  inativo: { label: 'Inativo', variant: 'default' },
  em_implantacao: { label: 'Em Implantacao', variant: 'warning' },
  suspenso: { label: 'Suspenso', variant: 'danger' },
};

const label = computed(() => {
  if (!props.status) return 'N/A';
  return config[props.status]?.label || props.status;
});

const variant = computed(() => config[props.status]?.variant ?? 'default');
</script>
