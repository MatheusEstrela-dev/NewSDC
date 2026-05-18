<template>
  <Head title="TDAP — Lotes" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Lotes de Fornecimento"
      description="Subdivisões das atas por município e prestador"
      :icon="MapIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.lotes.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Lote</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </TdapPageHeader>

    <TdapStatsRow :cards="statsCards" :columns="4" />

    <TdapLotesFiltersSection
      v-model:filters="activeFilters"
      :atas="atas"
      :prestadores="prestadores"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <TdapDataTable
      title="Lotes"
      subtitle="Subdivisões contratadas por município e prestador"
      :columns="columns"
      :rows="lotes.data"
      :pagination="lotes.meta"
      empty-text="Nenhum lote cadastrado."
    >
      <template #row="{ row: l }">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
          <td class="px-4 py-3 text-sm font-mono">
            <Link :href="route('tdap.lotes.show', l.id)" class="text-blue-600 hover:text-blue-800">{{ l.numero }}</Link>
            <span v-if="l.nome" class="block text-xs text-slate-500">{{ l.nome }}</span>
          </td>
          <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300">{{ l.ata_numero }}</td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
            {{ l.municipio_nome }}<span v-if="l.municipio_uf" class="text-slate-400">/{{ l.municipio_uf }}</span>
          </td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ l.prestador_nome }}</td>
          <td class="px-4 py-3 text-sm text-right font-mono">{{ fmtDecimal(l.qtd_agua_m3) }}</td>
          <td class="px-4 py-3 text-sm text-right font-mono">{{ fmtDecimal(l.valor_total) }}</td>
          <td class="px-4 py-3 text-sm">
            <TdapStatusBadge :active="!!l.ativo" />
          </td>
          <td class="px-4 py-3 text-right text-sm space-x-2">
            <Link :href="route('tdap.lotes.show', l.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
            <Link v-if="canEdit" :href="route('tdap.lotes.edit', l.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
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
import TdapLotesFiltersSection from '@/Components/Organisms/Tdap/TdapLotesFiltersSection.vue';
import TdapDataTable from '@/Components/Organisms/Tdap/Table/TdapDataTable.vue';
import TdapStatusBadge from '@/Components/Atoms/Tdap/TdapStatusBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  lotes:        { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, volume_total_m3: 0, valor_total: 0 }) },
  atas:         { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  ata_id:       props.filtros.ata_id ?? '',
  prestador_id: props.filtros.prestador_id ?? '',
  ativo:        props.filtros.ativo ?? '',
});

const columns = [
  { label: 'Lote', align: 'left' },
  { label: 'Ata', align: 'left' },
  { label: 'Município', align: 'left' },
  { label: 'Prestador', align: 'left' },
  { label: 'Volume (m³)', align: 'right' },
  { label: 'Valor total (R$)', align: 'right' },
  { label: 'Status', align: 'left' },
  { label: 'Ações', align: 'right' },
];

const statsCards = computed(() => [
  { title: 'Total',                  value: props.estatisticas.total,                                     variant: 'info',    icon: DocumentTextIcon },
  { title: 'Ativos',                 value: props.estatisticas.ativos,                                    variant: 'success', icon: CheckBadgeIcon },
  { title: 'Volume contratado (m³)', value: props.estatisticas.volume_total_m3, format: 'decimal',        variant: 'info',    icon: CubeIcon },
  { title: 'Valor total (R$)',       value: props.estatisticas.valor_total,    format: 'currency',       variant: 'warning', icon: CubeIcon },
]);

function fmtDecimal(v) {
  return Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.lotes.index'), {
    ata_id:       filters.ata_id || undefined,
    prestador_id: filters.prestador_id || undefined,
    ativo:        filters.ativo !== '' && filters.ativo !== undefined ? filters.ativo : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = { ata_id: '', prestador_id: '', ativo: '' };
  router.get(route('tdap.lotes.index'), {}, { preserveState: true, replace: true });
}
</script>
