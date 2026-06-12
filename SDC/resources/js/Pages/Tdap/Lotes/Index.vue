<template>
  <Head title="TDAP — Lotes" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Lotes de Fornecimento"
      description="Subdivisões das atas por município e prestador"
      :icon="MapIcon"
    >
      <template #actions>
        <Button variant="success" size="md" :icon="DownloadIcon" icon-position="left" @click="openExportModal">
          <span class="hidden sm:inline">Exportar</span>
          <span class="sm:hidden">CSV</span>
        </Button>
        <Link v-if="canCreate" :href="route('tdap.lotes.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Lote</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="DocumentIcon" variant="info" />
      <StatCard title="Ativos" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" />
      <StatCard title="Volume contratado (m³)" :value="Number(estatisticas.volume_total_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2})" :icon="CubeIcon" variant="info" :format-number="false" />
      <StatCard title="Valor total (R$)" :value="Number(estatisticas.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2})" :icon="CheckBadgeIcon" variant="info" :format-number="false" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Ata"
        type="select"
        :model-value="filtroAta"
        :options="ataOptions"
        @update:model-value="filtroAta = $event"
      />
      <FilterField
        label="Prestador"
        type="select"
        :model-value="filtroPrestador"
        :options="prestadorOptions"
        @update:model-value="filtroPrestador = $event"
      />
      <FilterField
        label="Status"
        type="select"
        :model-value="filtroAtivo"
        :options="ativoOptions"
        @update:model-value="filtroAtivo = $event"
      />
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Lote</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ata</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Município</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Prestador</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Volume (m³)</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Valor total (R$)</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="l in lotes.data" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono">
              <Link :href="route('tdap.lotes.show', l.id)" class="text-blue-600 hover:text-blue-800">{{ l.numero }}</Link>
              <span v-if="l.nome" class="block text-xs text-slate-500">{{ l.nome }}</span>
            </td>
            <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300">{{ l.ata_numero }}</td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ l.municipio_nome }}<span v-if="l.municipio_uf" class="text-slate-400">/{{ l.municipio_uf }}</span></td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ l.prestador_nome }}</td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(l.qtd_agua_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(l.valor_total).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm">
              <span :class="l.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ l.ativo ? 'Ativo' : 'Inativo' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="view"
                  module="tdap"
                  resource="lotes"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Visualizar lote"
                  @click="router.visit(route('tdap.lotes.show', l.id))"
                />
                <ActionButton
                  v-if="canEdit"
                  action="edit"
                  module="tdap"
                  resource="lotes"
                  :allowed="canEdit"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar lote"
                  @click="router.visit(route('tdap.lotes.edit', l.id))"
                />
              </div>
            </td>
          </tr>
          <tr v-if="lotes.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum lote cadastrado.</td>
          </tr>
        </tbody>
      </table>

      <div v-if="lotes.meta && lotes.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700 flex items-center justify-between">
        <p class="text-xs text-slate-500">
          Página {{ lotes.meta.current_page }} de {{ lotes.meta.last_page }} ({{ lotes.meta.total }} registros)
        </p>
        <div class="space-x-2">
          <Link
            v-for="(link, i) in lotes.meta.links || []"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1 text-sm rounded border"
            :class="link.active ? 'bg-blue-600 text-white border-blue-600' : 'border-slate-300 text-slate-700 dark:text-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800'"
          />
        </div>
      </div>
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Lotes"
      @close="closeExportModal"
      @export="onExport"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { computed } from 'vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineOptions({ layout: AuthenticatedLayout });

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

const filtroAta       = ref(props.filtros.ata_id ?? '');
const filtroPrestador = ref(props.filtros.prestador_id ?? '');
const filtroAtivo     = ref(props.filtros.ativo ?? '');

const ataOptions = computed(() => [
  { value: '', label: 'Todas as atas' },
  ...props.atas.map(a => ({ value: a.id, label: a.numero })),
]);
const prestadorOptions = computed(() => [
  { value: '', label: 'Todos os prestadores' },
  ...props.prestadores.map(p => ({ value: p.id, label: p.nome })),
]);
const ativoOptions = [
  { value: '', label: 'Todos' },
  { value: '1', label: 'Ativos' },
  { value: '0', label: 'Inativos' },
];

function aplicarFiltros() {
  router.get(route('tdap.lotes.index'), {
    ata_id:       filtroAta.value || undefined,
    prestador_id: filtroPrestador.value || undefined,
    ativo:        filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroAta.value = '';
  filtroPrestador.value = '';
  filtroAtivo.value = '';
  router.get(route('tdap.lotes.index'), {}, { preserveState: false });
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.lotes.export');

function onExport(params) {
  handleExport(params, {
    ata_id:       filtroAta.value || undefined,
    prestador_id: filtroPrestador.value || undefined,
    ativo:        filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  });
}
</script>
