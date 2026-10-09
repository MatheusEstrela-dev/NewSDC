<template>
  <CollapsibleSection namespace="pae" section-id="simulado-situacao" title="Situação anual" :subtitle="`Janela de 12 meses: de ${formatarData(resumo.janela_inicio)} até hoje`" :icon="CalendarDaysIcon" :tom="tom">
    <dl class="grid gap-3 text-sm sm:grid-cols-3">
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Situação</dt>
        <dd class="mt-1"><Badge :variant="varianteSituacaoSimulado(resumo.situacao)">{{ rotuloSituacaoSimulado(resumo.situacao) }}</Badge></dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Próximo vencimento</dt>
        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ resumo.proximo_vencimento ? formatarData(resumo.proximo_vencimento) : '—' }}</dd>
      </div>
      <div>
        <dt class="text-xs uppercase text-slate-500 dark:text-slate-400">Relatórios registrados</dt>
        <dd class="mt-1 text-slate-800 dark:text-slate-100">{{ resumo.relatorios.length }}</dd>
      </div>
    </dl>
    <PaeAviso v-if="resumo.alerta_legado" tom="aviso" class="mt-3">Este CCPAE é anterior ao registro de exigibilidade do simulado. A CEDEC deve avaliar o protocolo.</PaeAviso>
    <PaeAviso v-if="['vencido', 'nao_validado'].includes(resumo.situacao)" tom="erro" class="mt-3">Pendência para análise da CEDEC (reavaliação do PAE, Art. 101). O sistema não suspende nem revoga o CCPAE automaticamente.</PaeAviso>
    <PaeAviso v-if="resumo.situacao === 'pendente_emissao'" tom="aviso" class="mt-3">A emissão do CCPAE ficará bloqueada até haver relatório validado realizado nos 12 meses anteriores.</PaeAviso>
  </CollapsibleSection>
</template>

<script setup>
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import CollapsibleSection from '@/Components/Molecules/CollapsibleSection.vue';
import PaeAviso from '@/Components/Molecules/Pae/PaeAviso.vue';
import { rotuloSituacaoSimulado, varianteSituacaoSimulado } from '@/utils/paeSimulado';
import { formatarData } from '@/utils/paeTela';
import { CalendarDaysIcon } from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  resumo: { type: Object, required: true },
});

const tom = computed(() => {
  const variante = varianteSituacaoSimulado(props.resumo.situacao);
  return { success: 'success', danger: 'danger', warning: 'warning' }[variante] ?? 'neutro';
});
</script>
