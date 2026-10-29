<template>
  <span :class="['inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium', estilo.cor]">
    <span class="w-1.5 h-1.5 rounded-full mr-1.5" :class="estilo.ponto"></span>
    {{ rotulo }}
  </span>
</template>

<script setup>
import { computed } from 'vue';
import { estiloDaFase } from '@/Support/faseTramiteTdap';

const props = defineProps({
  // { value, label } vindo de FaseCronograma::toArray()
  fase:      { type: Object, required: true },
  // Legado: encerrado antes da fase Entregues; mostra Entregues igual.
  encerrado: { type: Boolean, default: false },
});

const estilo = computed(() => estiloDaFase(props.encerrado ? 'entregue' : props.fase?.value));
const rotulo = computed(() => (props.encerrado ? 'Entregues' : props.fase?.label ?? '—'));
</script>
