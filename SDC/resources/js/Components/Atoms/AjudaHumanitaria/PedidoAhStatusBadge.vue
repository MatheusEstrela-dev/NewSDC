<template>
  <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', classes.fundo, classes.texto]">
    <span :class="['mr-1.5 h-1.5 w-1.5 rounded-full', pontoClasse]"></span>
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue';

/**
 * Badge de status do pedido MAH.
 *
 * As classes vem do backend, em StatusPedidoAh::cor(), para que a cor de cada
 * fase tenha uma unica fonte de verdade. O componente so as aplica.
 */
const props = defineProps({
  status: { type: [Number, String], default: null },
  label: { type: String, default: '' },
  cor: { type: Object, default: null },
});

const classes = computed(() => props.cor ?? {
  fundo: 'bg-slate-100 dark:bg-slate-800',
  texto: 'text-slate-700 dark:text-slate-300',
});

// Deriva a cor do ponto do fundo, trocando a intensidade por uma solida.
const pontoClasse = computed(() => {
  const fundo = classes.value.fundo ?? '';
  const match = fundo.match(/bg-([a-z]+)-\d+/);

  return match ? `bg-${match[1]}-500` : 'bg-slate-500';
});
</script>
