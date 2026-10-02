<template>
  <SectionCard>
    <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">Histórico</h2>
    <ol v-if="eventos.length" class="space-y-3">
      <li v-for="evento in eventos" :key="evento.id" class="border-l-2 border-slate-200 pl-3 dark:border-slate-700">
        <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ rotuloAcaoAuditoria(evento.acao) }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ formatarDataHora(evento.created_at) }}</p>
      </li>
    </ol>
    <p v-else class="text-sm text-slate-500 dark:text-slate-400">Nenhum evento registrado.</p>
  </SectionCard>
</template>

<script setup>
import SectionCard from '@/Components/Atoms/Card/SectionCard.vue';
import { formatarDataHora } from '@/utils/dateFormatter';
import { rotuloAcaoAuditoria } from '@/Support/acessos';

defineProps({
  eventos: { type: Array, default: () => [] },
});
</script>
