<template>
  <Head title="TDAP — Processos" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Processos TDAP"
      description="Workflow de habilitação até liquidação"
      :icon="TruckIcon"
    >
      <template #actions>
        <ActionButton action="export" :allowed="true" variant="success" label="Exportar" @click="openExportModal" />
        <Link :href="route('tdap.processos.swimlanes')">
          <Button variant="secondary" size="md">
            <span class="hidden sm:inline">Ver Swimlanes</span>
            <span class="sm:hidden">Kanban</span>
          </Button>
        </Link>
        <Link v-if="canCreate" :href="route('tdap.processos.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Processo</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="ClipboardDocumentListIcon" variant="info"
        clickable @click="filtrarPorEstado('')" />
      <StatCard title="Abertos" :value="estatisticas.abertos ?? 0" :icon="DocumentTextIcon" variant="info" />
      <StatCard title="Em execução" :value="estatisticas.em_execucao ?? 0" :icon="CheckCircleIcon" variant="success"
        clickable @click="filtrarPorEstado('em_execucao')" />
      <StatCard title="Encerrados" :value="estatisticas.encerrados ?? 0" :icon="CheckIcon" variant="warning"
        clickable @click="filtrarPorEstado('encerrado')" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar número"
        type="text"
        :model-value="filtroSearch"
        placeholder="Ex: 001/2026"
        @update:model-value="filtroSearch = $event"
      />
      <FilterField
        label="Estado"
        type="select"
        :model-value="filtroEstado"
        :options="estadoOptions"
        @update:model-value="filtroEstado = $event"
      />
      <FilterField
        label="Swimlane"
        type="select"
        :model-value="filtroSwimlane"
        :options="swimlaneOptions"
        @update:model-value="filtroSwimlane = $event"
      />
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Estado</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Swimlane</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Município</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Aberto</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="p in processos.data" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 font-mono font-semibold">
              <Link :href="route('tdap.processos.show', p.id)" class="text-blue-600 hover:text-blue-800">{{ p.numero }}</Link>
            </td>
            <td class="px-4 py-3"><EstadoProcessoBadge :estado="p.estado" /></td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ p.swimlane_label }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ p.municipio_nome }}<span v-if="p.municipio_uf" class="text-slate-400">/{{ p.municipio_uf }}</span></td>
            <td class="px-4 py-3 text-xs text-slate-500">{{ fmtDate(p.aberto_em) }}</td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="view"
                  module="tdap"
                  resource="processos"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Visualizar processo"
                  @click="router.visit(route('tdap.processos.show', p.id))"
                />
              </div>
            </td>
          </tr>
          <tr v-if="processos.data.length === 0">
            <td colspan="6" class="px-4 py-12 text-center text-slate-400">Nenhum processo aberto.</td>
          </tr>
        </tbody>
      </table>

    </div>

    <div v-if="processos.meta && processos.meta.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="processos.meta" @page-change="irParaPagina" />
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Processos"
      @close="closeExportModal"
      @export="onExport"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import EstadoProcessoBadge from '@/Components/Organisms/Tdap/EstadoProcessoBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  processos:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total:0, abertos:0, em_execucao:0, encerrados:0 }) },
  estados:      { type: Array, default: () => [] },
  swimlanes:    { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canTransitar: { type: Boolean, default: false },
});

const filtroSearch   = ref(props.filtros.search ?? '');
const filtroEstado   = ref(props.filtros.estado ?? '');
const filtroSwimlane = ref(props.filtros.swimlane ?? '');

const estadoOptions = computed(() => [
  { value: '', label: 'Todos os estados' },
  ...props.estados.map(e => ({ value: e.value, label: e.label })),
]);

const swimlaneOptions = computed(() => [
  { value: '', label: 'Todas as swimlanes' },
  ...props.swimlanes.map(s => ({ value: s.value, label: s.label })),
]);

function aplicarFiltros() {
  router.get(route('tdap.processos.index'), {
    search:   filtroSearch.value || undefined,
    estado:   filtroEstado.value || undefined,
    swimlane: filtroSwimlane.value || undefined,
  }, { preserveState: true, replace: true });
}

function irParaPagina(page) {
  router.get(route('tdap.processos.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}

// Cards de estatistica como filtros rapidos: '' = Total (limpa o filtro de estado),
// preservando busca e swimlane, como no padrao PMDA.
function filtrarPorEstado(estado) {
  filtroEstado.value = estado || '';
  aplicarFiltros();
}

function limparFiltros() {
  filtroSearch.value = '';
  filtroEstado.value = '';
  filtroSwimlane.value = '';
  router.get(route('tdap.processos.index'), {}, { preserveState: false });
}

// Exportacao CSV (mesmo padrao do Cronograma)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.processos.export');

function onExport(params) {
  handleExport(params, {
    search:   filtroSearch.value || undefined,
    estado:   filtroEstado.value || undefined,
    swimlane: filtroSwimlane.value || undefined,
  });
}

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('pt-BR');
}
</script>
