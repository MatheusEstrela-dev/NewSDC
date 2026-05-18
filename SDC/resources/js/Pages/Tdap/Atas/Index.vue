<template>
  <Head title="TDAP — Atas" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Atas de Registro de Preços"
      description="Contratos-pai que autorizam o fornecimento de água potável"
      :icon="CalendarIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.atas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Nova Ata</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </Link>
      </template>
    </TdapPageHeader>

    <TdapStatsRow :cards="statsCards" :columns="4" />

    <TdapAtasFiltersSection
      v-model:filters="activeFilters"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <TdapDataTable
      title="Atas"
      subtitle="Contratos vigentes e histórico"
      :columns="columns"
      :rows="atas.data"
      :pagination="atas.meta"
      empty-text="Nenhuma ata cadastrada."
    >
      <template #row="{ row: a }">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
          <td class="px-4 py-3 text-sm font-mono font-semibold text-slate-900 dark:text-slate-100">
            <Link :href="route('tdap.atas.show', a.id)" class="hover:text-blue-600">{{ a.numero }}</Link>
          </td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
            {{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}
          </td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ a.lotes_count }}</td>
          <td class="px-4 py-3 text-sm">
            <TdapStatusBadge :state="ataState(a)" />
          </td>
          <td class="px-4 py-3 text-right text-sm space-x-2">
            <Link :href="route('tdap.atas.show', a.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
            <Link v-if="canEdit" :href="route('tdap.atas.edit', a.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
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
import TdapAtasFiltersSection from '@/Components/Organisms/Tdap/TdapAtasFiltersSection.vue';
import TdapDataTable from '@/Components/Organisms/Tdap/Table/TdapDataTable.vue';
import TdapStatusBadge from '@/Components/Atoms/Tdap/TdapStatusBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  atas:         { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, vigentes: 0, encerradas: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  search:  props.filtros.search ?? '',
  ativo:   props.filtros.ativo ?? '',
  vigente: props.filtros.vigente ? 1 : '',
});

const columns = [
  { label: 'Número', align: 'left' },
  { label: 'Vigência', align: 'left' },
  { label: 'Lotes', align: 'left' },
  { label: 'Status', align: 'left' },
  { label: 'Ações', align: 'right' },
];

const statsCards = computed(() => [
  { title: 'Total',      value: props.estatisticas.total,      variant: 'info',    icon: DocumentTextIcon },
  { title: 'Ativas',     value: props.estatisticas.ativos,     variant: 'success', icon: CheckBadgeIcon },
  { title: 'Vigentes',   value: props.estatisticas.vigentes,   variant: 'info',    icon: ClockIcon },
  { title: 'Encerradas', value: props.estatisticas.encerradas, variant: 'warning', icon: ArchiveBoxIcon },
]);

function ataState(a) {
  if (a.vigente) return 'vigente';
  return a.ativo ? 'ativa' : 'inativa';
}

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.atas.index'), {
    search:  filters.search || undefined,
    ativo:   filters.ativo !== '' && filters.ativo !== undefined ? filters.ativo : undefined,
    vigente: filters.vigente ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = { search: '', ativo: '', vigente: '' };
  router.get(route('tdap.atas.index'), {}, { preserveState: true, replace: true });
}

function formatDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
