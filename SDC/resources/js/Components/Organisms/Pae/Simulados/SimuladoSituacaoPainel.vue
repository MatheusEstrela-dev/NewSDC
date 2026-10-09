<template>
  <section class="min-w-0 rounded-xl border bg-white p-3 shadow-sm dark:bg-slate-900/40 sm:px-4" :class="BORDAS[tom]">
    <div class="flex flex-col gap-2 text-sm sm:flex-row sm:flex-wrap sm:items-center sm:gap-x-6 sm:gap-y-2">
      <div class="flex items-center gap-2">
        <CalendarDaysIcon class="h-5 w-5 shrink-0 text-slate-400" aria-hidden="true" />
        <span class="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">Situação anual</span>
        <Badge :variant="varianteSituacaoSimulado(resumo.situacao)">{{ rotuloSituacaoSimulado(resumo.situacao) }}</Badge>
      </div>
      <p class="text-slate-700 dark:text-slate-200"><span class="text-xs uppercase text-slate-500 dark:text-slate-400">Próximo vencimento</span> {{ resumo.proximo_vencimento ? formatarData(resumo.proximo_vencimento) : '—' }}</p>
      <p class="text-slate-700 dark:text-slate-200"><span class="text-xs uppercase text-slate-500 dark:text-slate-400">Relatórios</span> {{ resumo.relatorios.length }}</p>
      <p class="text-xs text-slate-500 dark:text-slate-400 sm:ml-auto">Janela de 12 meses: de {{ formatarData(resumo.janela_inicio) }} até hoje</p>
    </div>
    <PaeAviso v-if="resumo.alerta_legado" tom="aviso" class="mt-3">Este CCPAE é anterior ao registro de exigibilidade do simulado. A CEDEC deve avaliar o protocolo.</PaeAviso>
    <PaeAviso v-if="['vencido', 'nao_validado'].includes(resumo.situacao)" tom="erro" class="mt-3">Pendência para análise da CEDEC (reavaliação do PAE, Art. 101). O sistema não suspende nem revoga o CCPAE automaticamente.</PaeAviso>
    <PaeAviso v-if="resumo.situacao === 'pendente_emissao'" tom="aviso" class="mt-3">A emissão do CCPAE ficará bloqueada até haver relatório validado realizado nos 12 meses anteriores.</PaeAviso>
  </section>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';
import { formatarData } from '@/utils/paeTela';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  resumo: { type: Object, required: true },
});

// Classes literais: o Tailwind so enxerga o que esta escrito por extenso.
const BORDAS = {
  success: 'border-green-300 dark:border-green-700/60',
  danger: 'border-red-300 dark:border-red-700/60',
  warning: 'border-amber-300 dark:border-amber-700/60',
  neutro: 'border-slate-200 dark:border-slate-700/50',
};

const tom = computed(() => {
  const variante = varianteSituacaoSimulado(props.resumo.situacao);
  return { success: 'success', danger: 'danger', warning: 'warning' }[variante] ?? 'neutro';
});
</script>
