<script setup>
/**
 * Badge do status que a Area do Gestor usa (Planejado/Publicado/Em
 * andamento/Concluido/Cancelado - enum StatusExibicaoCurso no backend).
 *
 * `color` vem do backend (`status_exibicao_color`: gray/indigo/blue/green/red)
 * e mapeia para `cor` do atomo Badge, que nao tem "gray" (a paleta usa
 * "slate"). `variant` (do backend) existe mas nao chega a ser usado aqui: ele
 * repete "info" tanto para Publicado quanto para Em andamento, e so a cor
 * distingue os dois - por isso o mapeamento abaixo e sempre por `color`.
 */
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

const props = defineProps({
  color: { type: String, default: 'gray' },
  label: { type: String, required: true },
  size: { type: String, default: 'md' },
});

const MAPA_COR = {
  gray: 'slate',
  indigo: 'indigo',
  blue: 'blue',
  green: 'green',
  red: 'red',
};

const corBadge = computed(() => MAPA_COR[props.color] ?? 'slate');
</script>

<template>
  <Badge :cor="corBadge" :size="size">{{ label }}</Badge>
</template>
