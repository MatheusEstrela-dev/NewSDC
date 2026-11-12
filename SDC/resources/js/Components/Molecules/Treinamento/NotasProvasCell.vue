<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { formatarNota } from '@/Composables/treinamento/formatarNota';

/**
 * Notas de um aluno nas provas do curso. Cada prova travada (tentativas
 * esgotadas sem aprovacao) oferece a acao de liberar tentativa.
 */
defineProps({
  notas: { type: Array, default: () => [] },
});
defineEmits(['liberar']);

</script>

<template>
  <span v-if="notas.length === 0" class="text-slate-400">-</span>
  <ul v-else class="space-y-1.5">
    <li v-for="n in notas" :key="n.prova_id" class="flex flex-wrap items-center gap-x-2 gap-y-1">
      <span class="max-w-[12rem] truncate" :title="n.titulo">{{ n.titulo }}</span>
      <span class="font-medium tabular-nums">{{ formatarNota(n.nota_final, n.escala_nota) }}</span>
      <Badge v-if="n.aprovada" variant="success" size="sm">Aprovado</Badge>
      <span class="text-xs tabular-nums text-slate-500 dark:text-slate-400">{{ n.tentativas_enviadas }}/{{ n.tentativas_permitidas }}</span>
      <template v-if="n.travada">
        <Badge variant="danger" size="sm">Travado</Badge>
        <Button size="sm" variant="outline" type="button" @click="$emit('liberar', n)">Liberar tentativa</Button>
      </template>
    </li>
  </ul>
</template>
