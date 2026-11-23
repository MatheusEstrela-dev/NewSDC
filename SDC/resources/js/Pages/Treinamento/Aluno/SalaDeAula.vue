<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SalaDeAulaTemplate from '@/Templates/Treinamento/SalaDeAulaTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  curso: { type: Object, required: true },
  estado: { type: Object, required: true },
  escopo: { type: String, default: 'aluno' },
  versao_rotulo: { type: String, default: null },
  avaliacao_final: { type: Object, default: null },
  notas_avaliacao: { type: Array, default: () => [] },
  notas_relevancia: { type: Array, default: () => [] },
});
</script>

<template>
  <SalaDeAulaTemplate :curso="curso" :estado="estado" :escopo="escopo" :versao-rotulo="versao_rotulo" :avaliacao-final="avaliacao_final" :notas-avaliacao="notas_avaliacao" :notas-relevancia="notas_relevancia" />
</template>
