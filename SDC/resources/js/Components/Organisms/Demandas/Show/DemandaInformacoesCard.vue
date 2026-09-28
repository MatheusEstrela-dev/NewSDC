<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">Informações</h2>
    <p class="text-xs text-slate-500">Status</p>
    <FilterField v-if="podeEditar && opcoesEtapa.length > 1" :model-value="demanda.etapa" label="" type="select" :options="opcoesEtapa" @update:model-value="mudar" />
    <DemandaStatusBadge v-else :etapa="demanda.etapa" :label="demanda.etapa_label" />
    <p v-if="erroStatus" class="mt-1 text-xs text-red-500">{{ erroStatus }}</p>
    <dl class="mt-4 space-y-3 text-sm">
      <div><dt class="text-xs text-slate-500">Categoria</dt><dd class="text-slate-900 dark:text-slate-100">{{ demanda.assunto?.categoria ?? '—' }}</dd></div>
      <div><dt class="text-xs text-slate-500">Prioridade</dt><dd><DemandaPrioridadeBadge :prioridade="demanda.prioridade_simples" :label="demanda.prioridade_label" :itil="demanda.prioridade_itil" /></dd></div>
      <div><dt class="text-xs text-slate-500">Data de abertura</dt><dd class="text-slate-900 dark:text-slate-100">{{ formatarDataHora(demanda.created_at) }}</dd></div>
      <div v-if="demanda.prazo_resolucao"><dt class="text-xs text-slate-500">Prazo (SLA)</dt>
        <dd :class="demanda.sla_resolucao_violado ? 'text-red-500' : 'text-slate-900 dark:text-slate-100'">{{ formatarDataHora(demanda.prazo_resolucao) }}<span v-if="demanda.sla_resolucao_violado"> · vencido</span></dd></div>
      <div v-if="demanda.legado_id"><dt class="text-xs text-slate-500">Chamado no sistema anterior</dt><dd class="text-slate-900 dark:text-slate-100">#{{ demanda.legado_id }}</dd></div>
    </dl>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import DemandaStatusBadge from '@/Components/Atoms/Demandas/DemandaStatusBadge.vue';
import DemandaPrioridadeBadge from '@/Components/Atoms/Demandas/DemandaPrioridadeBadge.vue';
import { formatarDataHora } from '@/Support/demandasFormat';

const props = defineProps({
  demanda: { type: Object, required: true },
  podeEditar: { type: Boolean, default: false },
});
const emit = defineEmits(['concluir']);

// "Concluido" nao muda status direto: abre o card de resolucao, que pede datas.
const ALVO = { em_andamento: 'em_progresso', cancelado: 'cancelada' };
const opcoesEtapa = computed(() => {
  if (!['aberto', 'em_andamento'].includes(props.demanda.etapa)) return [];
  const base = [{ value: props.demanda.etapa, label: props.demanda.etapa_label }];
  if (props.demanda.etapa === 'aberto') base.push({ value: 'em_andamento', label: 'Em andamento' });
  base.push({ value: 'concluido', label: 'Concluído' }, { value: 'cancelado', label: 'Cancelado' });
  return base;
});
const erroStatus = computed(() => usePage().props.errors?.status);

function mudar(etapa) {
  if (etapa === props.demanda.etapa) return;
  if (etapa === 'concluido') { emit('concluir'); return; }
  router.post(route('admin.demandas.change-status', props.demanda.id), { status: ALVO[etapa] }, { preserveScroll: true });
}
</script>
