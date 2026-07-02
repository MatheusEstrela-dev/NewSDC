<template>
  <Head title="TDAP - Prestadores" />

  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Prestadores TDAP"
      description="Empresas contratadas para transporte de água potável"
      :icon="BuildingIcon"
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
          resource="prestadores"
          label="Novo Prestador"
          :allowed="canCreate"
          @click="router.visit(route('tdap.prestadores.create'))"
        />
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
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
    </div>

    <TdapPrestadoresFiltersSection
      v-model:filters="activeFilters"
      @apply="aplicarFiltros"
      @clear="limparFiltros"
    />

    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
      <div class="border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
        <div class="flex items-center justify-between gap-3">
          <div class="min-w-0">
            <h3 class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">Prestadores</h3>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Cadastro e situacao das empresas contratadas</p>
          </div>
          <span class="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 dark:border-blue-500/25 dark:bg-blue-500/15 dark:text-blue-300">
            {{ prestadores.meta?.total ?? prestadores.data.length }}
          </span>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left">CNPJ</th>
              <th class="px-4 py-3 text-left">Razão Social</th>
              <th class="px-4 py-3 text-left">Cidade/UF</th>
              <th class="px-4 py-3 text-left">Caminhões</th>
              <th class="px-4 py-3 text-left">Status</th>
              <th class="w-28 px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr
              v-for="prestador in prestadores.data"
              :key="prestador.id"
              class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60"
            >
              <td class="whitespace-nowrap px-4 py-4 font-mono text-slate-700 dark:text-slate-300">
                {{ prestador.cnpj_formatado }}
              </td>
              <td class="px-4 py-4">
                <Link
                  :href="route('tdap.prestadores.show', prestador.id)"
                  class="font-semibold text-slate-900 transition hover:text-blue-600 dark:text-slate-100"
                >
                  {{ prestador.nome }}
                </Link>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ prestador.email }}</p>
              </td>
              <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">
                <span v-if="prestador.cidade || prestador.uf">
                  {{ prestador.cidade }}<span v-if="prestador.uf">/{{ prestador.uf }}</span>
                </span>
                <span v-else class="text-slate-400">-</span>
              </td>
              <td class="whitespace-nowrap px-4 py-4 font-semibold text-slate-700 dark:text-slate-300">
                {{ prestador.caminhoes_count }}
              </td>
              <td class="whitespace-nowrap px-4 py-4">
                <TdapStatusBadge :active="prestador.ativo" />
              </td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-1">
                  <ActionButton
                    action="view"
                    module="tdap"
                    resource="prestadores"
                    :allowed="true"
                    :show-label="false"
                    size="sm"
                    tooltip-text="Visualizar prestador"
                    @click="router.visit(route('tdap.prestadores.show', prestador.id))"
                  />
                  <ActionButton
                    action="edit"
                    module="tdap"
                    resource="prestadores"
                    :allowed="canEdit"
                    :show-label="false"
                    size="sm"
                    tooltip-text="Editar prestador"
                    @click="router.visit(route('tdap.prestadores.edit', prestador.id))"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="prestadores.data.length === 0">
              <td colspan="6" class="px-4 py-10 text-center">
                <BuildingIcon class="mx-auto h-12 w-12 text-slate-400" />
                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum prestador encontrado</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros ou cadastre um novo prestador.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <div v-if="prestadores.meta && prestadores.meta.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="prestadores.meta" @page-change="irParaPagina" />
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Prestadores"
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
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapPrestadoresFiltersSection from '@/Components/Organisms/Tdap/TdapPrestadoresFiltersSection.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestadores:  { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, inativos: 0 }) },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const activeFilters = ref({
  search: props.filtros.search ?? '',
  ativo: props.filtros.ativo ?? '',
});

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.prestadores.index'), {
    search: filters.search || undefined,
    ativo:  filters.ativo !== '' ? filters.ativo : undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  activeFilters.value = {};
  router.get(route('tdap.prestadores.index'), {}, { preserveState: true, replace: true });
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.prestadores.export');

function onExport(params) {
  handleExport(params, {
    search: activeFilters.value.search || undefined,
    ativo:  activeFilters.value.ativo !== '' ? activeFilters.value.ativo : undefined,
  });
}
function irParaPagina(page) {
  router.get(route('tdap.prestadores.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}
</script>
