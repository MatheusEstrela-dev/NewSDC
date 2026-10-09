<template>
  <ul class="space-y-1">
    <li v-for="item in itens" :key="item.rotulo" class="flex items-center gap-2 text-xs">
      <span class="w-16 shrink-0 font-medium text-slate-600 dark:text-slate-400">{{ item.rotulo }}</span>
      <Badge :variant="item.variante" size="sm">{{ item.texto }}</Badge>
    </li>
  </ul>
</template>

<script setup>
import { computed } from 'vue';
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { rotuloSituacaoDco, varianteSituacaoDco } from '@/utils/paeDco';
import { rotuloSituacaoEvacuacao, varianteSituacaoEvacuacao } from '@/utils/paeEvacuacao';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';

const props = defineProps({
  protocolo: {
    type: Object,
    required: true,
  },
});

const itens = computed(() => {
  const { dcoSituacao, evacuacaoSituacao, simuladoSituacao } = props.protocolo;

  return [
    dcoSituacao && { rotulo: 'DCO', texto: rotuloSituacaoDco(dcoSituacao), variante: varianteSituacaoDco(dcoSituacao) },
    evacuacaoSituacao && { rotulo: 'Evacuação', texto: rotuloSituacaoEvacuacao(evacuacaoSituacao), variante: varianteSituacaoEvacuacao(evacuacaoSituacao) },
    simuladoSituacao && { rotulo: 'Simulado', texto: rotuloSituacaoSimulado(simuladoSituacao), variante: varianteSituacaoSimulado(simuladoSituacao) },
  ].filter(Boolean);
});
</script>
