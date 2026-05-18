<template>
  <Head title="TDAP — Vistorias" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Vistorias de Veículos"
      description="Inspeções técnicas dos caminhões-tanque (vigência 12 meses)"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.vistorias.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Nova Vistoria</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </Link>
      </template>
    </TdapPageHeader>

    <TdapStatsRow :cards="statsCards" :columns="5" />

    <TdapVistoriasFiltersSection
      v-model:filters="activeFilters"
      :caminhoes="caminhoes"
      :pareceres="pareceres"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <TdapDataTable
      title="Vistorias"
      subtitle="Inspeções técnicas dos caminhões-tanque"
      :columns="columns"
      :rows="vistorias.data"
      :pagination="vistorias.meta"
      empty-text="Nenhuma vistoria cadastrada."
    >
      <template #row="{ row: v }">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ fmtDate(v.data) }}</td>
          <td class="px-4 py-3 text-sm">
            <Link :href="route('tdap.vistorias.show', v.id)" class="font-mono font-semibold text-blue-600 hover:text-blue-800">{{ v.caminhao_placa }}</Link>
            <p v-if="v.caminhao_modelo" class="text-xs text-slate-500">{{ v.caminhao_modelo }}</p>
          </td>
          <td class="px-4 py-3 text-sm">
            <p>{{ v.nome }}</p>
            <p v-if="v.prestador_nome" class="text-xs text-slate-500">{{ v.prestador_nome }}</p>
          </td>
          <td class="px-4 py-3 text-sm font-mono text-xs text-slate-700 dark:text-slate-300">{{ v.ficha || '—' }}</td>
          <td class="px-4 py-3 text-sm">
            <TdapStatusBadge :state="v.parecer === 'aprovada' ? 'aprovada' : 'reprovada'" :label="v.parecer_label" />
          </td>
          <td class="px-4 py-3 text-sm">
            <TdapStatusBadge :state="v.esta_vigente ? 'vigente' : 'expirada'" />
          </td>
          <td class="px-4 py-3 text-right text-sm space-x-2">
            <Link :href="route('tdap.vistorias.show', v.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
            <Link v-if="canEdit" :href="route('tdap.vistorias.edit', v.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
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
import TdapVistoriasFiltersSection from '@/Components/Organisms/Tdap/TdapVistoriasFiltersSection.vue';
import TdapDataTable from '@/Components/Organisms/Tdap/Table/TdapDataTable.vue';
import TdapStatusBadge from '@/Components/Atoms/Tdap/TdapStatusBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import XMarkIcon from '@/Components/Icons/XMarkIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  vistorias:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, aprovadas: 0, reprovadas: 0, vigentes: 0, expiradas: 0 }) },
  caminhoes:    { type: Array, default: () => [] },
  pareceres:    { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  search:   props.filtros.search ?? '',
  placa_id: props.filtros.placa_id ?? '',
  parecer:  props.filtros.parecer ?? '',
  vigente:  props.filtros.vigente ? 1 : '',
});

const columns = [
  { label: 'Data', align: 'left' },
  { label: 'Caminhão', align: 'left' },
  { label: 'Vistoriador', align: 'left' },
  { label: 'Ficha', align: 'left' },
  { label: 'Parecer', align: 'left' },
  { label: 'Vigência', align: 'left' },
  { label: 'Ações', align: 'right' },
];

const statsCards = computed(() => [
  { title: 'Total',      value: props.estatisticas.total,      variant: 'info',    icon: DocumentTextIcon },
  { title: 'Aprovadas',  value: props.estatisticas.aprovadas,  variant: 'success', icon: CheckBadgeIcon },
  { title: 'Vigentes',   value: props.estatisticas.vigentes,   variant: 'info',    icon: ClockIcon },
  { title: 'Expiradas',  value: props.estatisticas.expiradas,  variant: 'warning', icon: ExclamationTriangleIcon },
  { title: 'Reprovadas', value: props.estatisticas.reprovadas, variant: 'danger',  icon: XMarkIcon },
]);

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.vistorias.index'), {
    search:   filters.search || undefined,
    placa_id: filters.placa_id || undefined,
    parecer:  filters.parecer || undefined,
    vigente:  filters.vigente ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = { search: '', placa_id: '', parecer: '', vigente: '' };
  router.get(route('tdap.vistorias.index'), {}, { preserveState: true, replace: true });
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
