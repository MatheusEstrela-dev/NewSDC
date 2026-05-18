<template>
  <Head title="TDAP — Cronogramas" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Cronogramas de Fornecimento"
      description="Ordens operacionais de entrega de água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link v-if="canCreate" :href="route('tdap.cronogramas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Cronograma</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </TdapPageHeader>

    <TdapStatsRow :cards="statsCards" :columns="5" />

    <TdapCronogramasFiltersSection
      v-model:filters="activeFilters"
      :atas="atas"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <TdapDataTable
      title="Cronogramas"
      subtitle="Ordens operacionais ativas e histórico"
      :columns="columns"
      :rows="cronogramas.data"
      :pagination="cronogramas.meta"
      empty-text="Nenhum cronograma cadastrado."
    >
      <template #row="{ row: c }">
        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
          <td class="px-4 py-3 text-sm font-mono">
            <Link :href="route('tdap.cronogramas.show', c.id)" class="text-blue-600 hover:text-blue-800 font-semibold">{{ c.numero }}</Link>
          </td>
          <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
            {{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}
          </td>
          <td class="px-4 py-3 text-sm">
            <p class="font-mono">{{ c.ata_numero }}</p>
            <p class="text-xs text-slate-500 font-mono">{{ c.lote_numero }}</p>
          </td>
          <td class="px-4 py-3 text-sm">
            <p>{{ c.municipio_nome }}<span v-if="c.municipio_uf" class="text-slate-400">/{{ c.municipio_uf }}</span></p>
            <p class="text-xs text-slate-500">{{ c.prestador_nome }}</p>
          </td>
          <td class="px-4 py-3 text-sm text-right font-mono">{{ fmtDecimal(c.volume_contratado_m3) }}</td>
          <td class="px-4 py-3 text-sm text-center">{{ c.caminhoes_count }}</td>
          <td class="px-4 py-3 text-sm">
            <EstadoBadge :estado="c.estado" />
          </td>
          <td class="px-4 py-3 text-right text-sm space-x-2">
            <Link :href="route('tdap.cronogramas.show', c.id)" class="text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200">Ver</Link>
            <Link v-if="canEdit && c.estado === 'rascunho'" :href="route('tdap.cronogramas.edit', c.id)" class="text-blue-600 hover:text-blue-800">Editar</Link>
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
import TdapCronogramasFiltersSection from '@/Components/Organisms/Tdap/TdapCronogramasFiltersSection.vue';
import TdapDataTable from '@/Components/Organisms/Tdap/Table/TdapDataTable.vue';
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  cronogramas:  { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, rascunhos: 0, encerrados: 0, volume_ativo_m3: 0 }) },
  atas:         { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canAtivar:    { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  search: props.filtros.search ?? '',
  estado: props.filtros.estado ?? '',
  ata_id: props.filtros.ata_id ?? '',
});

const columns = [
  { label: 'Número', align: 'left' },
  { label: 'Vigência', align: 'left' },
  { label: 'Ata / Lote', align: 'left' },
  { label: 'Município / Prestador', align: 'left' },
  { label: 'Volume (m³)', align: 'right' },
  { label: 'Caminhões', align: 'center' },
  { label: 'Estado', align: 'left' },
  { label: 'Ações', align: 'right' },
];

const statsCards = computed(() => [
  { title: 'Total',             value: props.estatisticas.total,                                 variant: 'info',    icon: DocumentTextIcon },
  { title: 'Ativos',            value: props.estatisticas.ativos,                                variant: 'success', icon: CheckBadgeIcon },
  { title: 'Rascunhos',         value: props.estatisticas.rascunhos,                             variant: 'warning', icon: PencilSquareIcon },
  { title: 'Encerrados',        value: props.estatisticas.encerrados,                            variant: 'danger',  icon: ArchiveBoxIcon },
  { title: 'Volume ativo (m³)', value: props.estatisticas.volume_ativo_m3, format: 'integer',    variant: 'info',    icon: CubeIcon },
]);

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}

function fmtDecimal(v) {
  return Number(v || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.cronogramas.index'), {
    search: filters.search || undefined,
    estado: filters.estado || undefined,
    ata_id: filters.ata_id || undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = { search: '', estado: '', ata_id: '' };
  router.get(route('tdap.cronogramas.index'), {}, { preserveState: true, replace: true });
}
</script>
