<template>
  <Head title="TDAP - Caminhões" />

  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Caminhões-Tanque"
      description="Frota dos prestadores autorizados a transportar água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <ActionButton
          action="export"
          :allowed="true"
          variant="success"
          label="Exportar"
          @click="openExportModal"
        />
        <ActionButton
          action="create"
          module="tdap"
          resource="caminhoes"
          label="Novo Caminhão"
          :allowed="canCreate"
          @click="router.visit(route('tdap.caminhoes.create'))"
        />
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <p class="text-sm text-slate-500 dark:text-slate-400">Total</p>
        <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ estatisticas.total }}</p>
      </div>
      <div class="rounded-lg border border-emerald-200 bg-white p-4 shadow-sm dark:border-emerald-500/25 dark:bg-slate-900/60">
        <p class="text-sm text-slate-500 dark:text-slate-400">Ativos</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ estatisticas.ativos }}</p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
        <p class="text-sm text-slate-500 dark:text-slate-400">Inativos</p>
        <p class="mt-2 text-2xl font-bold text-slate-500 dark:text-slate-300">{{ estatisticas.inativos }}</p>
      </div>
      <div class="rounded-lg border border-blue-200 bg-white p-4 shadow-sm dark:border-blue-500/25 dark:bg-slate-900/60">
        <p class="text-sm text-slate-500 dark:text-slate-400">Capacidade total (m³)</p>
        <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-300">{{ Number(estatisticas.capacidade_total_m3 || 0).toFixed(2) }}</p>
      </div>
    </div>

    <TdapCaminhoesFiltersSection
      v-model:filters="activeFilters"
      :prestadores="prestadores"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Caminhões-tanque</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Placas, prestadores e capacidade operacional</p>
          </div>
          <span class="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:border-blue-500/25 dark:bg-blue-500/15 dark:text-blue-300">
            {{ caminhoes.meta?.total ?? caminhoes.data.length }}
          </span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left">Placa</th>
              <th class="px-4 py-3 text-left">Prestador</th>
              <th class="px-4 py-3 text-left">Marca / Modelo</th>
              <th class="px-4 py-3 text-right">Capacidade (m³)</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="w-28 px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr
              v-for="caminhao in caminhoes.data"
              :key="caminhao.id"
              class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
            >
              <td class="whitespace-nowrap px-4 py-4">
                <Link
                  :href="route('tdap.caminhoes.show', caminhao.id)"
                  class="font-mono font-bold text-slate-900 transition hover:text-blue-600 dark:text-slate-100"
                >
                  {{ caminhao.placa }}
                </Link>
              </td>
              <td class="px-4 py-4">
                <p class="font-semibold text-slate-900 dark:text-slate-100">{{ caminhao.prestador_nome }}</p>
                <p class="mt-0.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ caminhao.prestador_cnpj }}</p>
              </td>
              <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                <span v-if="caminhao.marca || caminhao.modelo">
                  {{ caminhao.marca }} {{ caminhao.modelo }}
                  <span v-if="caminhao.ano" class="text-slate-400">({{ caminhao.ano }})</span>
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="whitespace-nowrap px-4 py-4 text-right font-mono text-slate-700 dark:text-slate-300">
                {{ Number(caminhao.capacidade_m3 || 0).toFixed(2) }}
              </td>
              <td class="whitespace-nowrap px-4 py-4">
                <TdapStatusBadge :active="caminhao.ativo" />
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-1">
                  <ActionButton
                    action="view"
                    module="tdap"
                    resource="caminhoes"
                    :allowed="true"
                    :show-label="false"
                    size="sm"
                    tooltip-text="Visualizar caminhão"
                    @click="router.visit(route('tdap.caminhoes.show', caminhao.id))"
                  />
                  <ActionButton
                    action="edit"
                    module="tdap"
                    resource="caminhoes"
                    :allowed="canEdit"
                    :show-label="false"
                    size="sm"
                    tooltip-text="Editar caminhão"
                    @click="router.visit(route('tdap.caminhoes.edit', caminhao.id))"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="caminhoes.data.length === 0">
              <td colspan="6" class="px-4 py-10 text-center">
                <TruckIcon class="mx-auto h-12 w-12 text-slate-400" />
                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum caminhão encontrado</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros ou cadastre um novo caminhão.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="caminhoes.meta && caminhoes.meta.last_page > 1" class="px-4 py-3 border-t border-slate-200 dark:border-slate-700/50">
        <Pagination :pagination="caminhoes.meta" @page-change="irParaPagina" />
      </div>
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Caminhoes"
      @close="closeExportModal"
      @export="onExport"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import TdapStatusBadge from '@/Components/Atoms/Tdap/TdapStatusBadge.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapCaminhoesFiltersSection from '@/Components/Organisms/Tdap/TdapCaminhoesFiltersSection.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  caminhoes:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, inativos: 0, capacidade_total_m3: 0 }) },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  search: props.filtros.search ?? '',
  prestador_id: props.filtros.prestador_id ?? '',
  ativo: props.filtros.ativo ?? '',
});

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.caminhoes.index'), {
    search:       filters.search || undefined,
    prestador_id: filters.prestador_id || undefined,
    ativo:        filters.ativo !== '' ? filters.ativo : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = {};
  router.get(route('tdap.caminhoes.index'), {}, { preserveState: true, replace: true });
}

// Exportacao CSV (mesmo padrao do Cronograma)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.caminhoes.export');

function onExport(params) {
  handleExport(params, {
    search:       activeFilters.value.search || undefined,
    prestador_id: activeFilters.value.prestador_id || undefined,
    ativo:        activeFilters.value.ativo !== '' ? activeFilters.value.ativo : undefined,
  });
}
function irParaPagina(page) {
  router.get(route('tdap.caminhoes.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}
</script>
