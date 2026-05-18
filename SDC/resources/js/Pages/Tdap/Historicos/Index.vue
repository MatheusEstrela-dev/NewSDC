<template>
  <Head title="TDAP — Histórico" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Histórico TDAP"
      description="Auditoria cronológica de eventos de negócio"
      :icon="ClockIcon"
    />

    <TdapStatsRow :cards="statsCards" :columns="3" />

    <TdapHistoricoFiltersSection
      v-model:filters="activeFilters"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <TdapDataTable
      title="Eventos"
      subtitle="Auditoria cronológica do módulo TDAP"
      :columns="columns"
      :rows="historicos.data"
      :pagination="historicos.meta"
      empty-text="Nenhum evento registrado."
    >
      <template #row="{ row: h }">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
          <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">{{ fmtDateTime(h.data_evento) }}</td>
          <td class="px-4 py-3">
            <span :class="badgeTipo(h.tipo_evento)" class="px-2 py-0.5 rounded text-xs font-mono">{{ h.tipo_evento }}</span>
          </td>
          <td class="px-4 py-3 text-xs font-mono text-slate-700 dark:text-slate-300">{{ h.entity_type }}#{{ h.entity_id }}</td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ h.obs || '—' }}</td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ h.user?.name || 'Sistema' }}</td>
          <td class="px-4 py-3 text-right">
            <Link :href="route('tdap.historicos.show', h.id)" class="text-blue-600 hover:text-blue-800 text-xs">Detalhes</Link>
          </td>
        </tr>
      </template>
    </TdapDataTable>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapStatsRow from '@/Components/Organisms/Tdap/Statistics/TdapStatsRow.vue';
import TdapHistoricoFiltersSection from '@/Components/Organisms/Tdap/TdapHistoricoFiltersSection.vue';
import TdapDataTable from '@/Components/Organisms/Tdap/Table/TdapDataTable.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  historicos:   { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, mes_atual: 0, cronogramas_envolvidos: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
});

const activeFilters = ref({
  entity_type: props.filtros.entity_type ?? '',
  tipo_evento: props.filtros.tipo_evento ?? '',
  de:          props.filtros.de ?? '',
  ate:         props.filtros.ate ?? '',
});

const columns = [
  { label: 'Quando', align: 'left' },
  { label: 'Tipo', align: 'left' },
  { label: 'Entidade', align: 'left' },
  { label: 'Mensagem', align: 'left' },
  { label: 'Usuário', align: 'left' },
  { label: 'Ações', align: 'right' },
];

const statsCards = computed(() => [
  { title: 'Total de eventos',       value: props.estatisticas.total,                  variant: 'info',    icon: ClockIcon },
  { title: 'Este mês',               value: props.estatisticas.mes_atual,              variant: 'success', icon: CalendarIcon },
  { title: 'Cronogramas envolvidos', value: props.estatisticas.cronogramas_envolvidos, variant: 'warning', icon: TruckIcon },
]);

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.historicos.index'), {
    entity_type: filters.entity_type || undefined,
    tipo_evento: filters.tipo_evento || undefined,
    de:          filters.de || undefined,
    ate:         filters.ate || undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = { entity_type: '', tipo_evento: '', de: '', ate: '' };
  router.get(route('tdap.historicos.index'), {}, { preserveState: true, replace: true });
}

function badgeTipo(tipo) {
  if (!tipo) return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
  if (tipo.includes('ativad') || tipo.includes('aprov')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
  if (tipo.includes('encerrad') || tipo.includes('reprov') || tipo.includes('rejeit')) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
  if (tipo.includes('criad') || tipo.includes('registrad')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
  if (tipo.includes('prorrog')) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
}

function fmtDateTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleString('pt-BR');
}
</script>
