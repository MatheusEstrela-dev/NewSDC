<template>
  <Head title="Gestão de PMDA" />

  <div class="pb-6">
    <PageHeader
      title="Gestão de PMDA"
      description="Planos Municipais de Defesa Agropecuária — fornecimento emergencial de água potável."
      :icon="DocumentTextIcon"
      :icon-image="moduleIcon('pmda')"
      variant="gradient"
    >
      <template #actions>
        <div class="flex w-full flex-wrap items-center justify-end gap-2">
          <ViewModeToggle v-model="viewMode" />
          <button
            v-if="can('pmda.comunidades.aprovar')"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:brightness-95"
            style="background-color: #00AAFF;"
            title="Solicitações de comunidades enviadas pelos municípios (análise CEDEC)"
            @click="router.visit(route('pmda.solicitacoes.index'))"
          >
            <InboxArrowDownIcon class="h-4 w-4" />
            Solicitações PMDA
          </button>
          <ActionButton
            v-if="can('pmda.planos.export')"
            action="export"
            :allowed="true"
            variant="success"
            label="Exportar"
            @click="openExportModal"
          />
          <ActionButton
            v-if="can('pmda.planos.create')"
            action="create"
            module="pmda"
            resource="planos"
            label="Novo PMDA"
            :allowed="true"
            @click="showCriar = true"
          />
        </div>
      </template>
    </PageHeader>

    <PmdaStatisticsCards :statistics="statistics" />

    <PmdaFiltersSection
      class="mb-6"
      :filters="filtros"
      :status-opcoes="statusOpcoes"
      :municipios="municipios"
      @apply="aplicar"
      @clear="limpar"
    />

    <!-- Tabela (md+) -->
    <div v-if="viewMode === 'table'" class="hidden overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60 md:block">
      <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700/50 dark:bg-slate-800/70">
        <h3 class="flex items-center gap-2 text-sm font-bold text-slate-900 dark:text-slate-100">
          <DocumentTextIcon class="h-4 w-4 text-slate-400" />
          Histórico dos PMDA
          <span class="font-normal text-slate-400">({{ planos.data.length }} registros)</span>
        </h3>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
            <tr>
              <th class="px-4 py-3 text-left">Protocolo</th>
              <th class="px-4 py-3 text-left">Município</th>
              <th class="px-4 py-3 text-left">Situação</th>
              <th class="px-4 py-3 text-left">Criação</th>
              <th class="w-28 px-4 py-3 text-right">Ações</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
            <tr v-for="plano in planos.data" :key="plano.id" class="transition hover:bg-slate-50 dark:hover:bg-slate-800/60">
              <td class="whitespace-nowrap px-4 py-4 font-mono text-slate-700 dark:text-slate-300">{{ plano.protocolo ?? '—' }}</td>
              <td class="px-4 py-4 text-slate-700 dark:text-slate-300">{{ plano.municipio ?? '—' }}</td>
              <td class="px-4 py-4"><PmdaStatusBadge :label="plano.status_label" :color-class="plano.status_color" /></td>
              <td class="whitespace-nowrap px-4 py-4 text-slate-500">{{ formatDate(plano.data) }}</td>
              <td class="px-4 py-4">
                <div class="flex items-center justify-end gap-1">
                  <ActionButton
                    action="edit" module="pmda" resource="planos"
                    :allowed="can('pmda.planos.edit')" :show-label="false" size="sm"
                    tooltip-text="Editar PMDA"
                    @click="router.visit(route('pmda.planos.edit', plano.id))"
                  />
                  <ActionButton
                    v-if="plano.pode_copiar"
                    action="duplicate" module="pmda" resource="planos"
                    :allowed="can('pmda.planos.copiar')" :show-label="false" size="sm"
                    tooltip-text="Criar cópia"
                    @click="copiar(plano.id)"
                  />
                </div>
              </td>
            </tr>

            <tr v-if="planos.data.length === 0">
              <td colspan="5" class="px-4 py-12 text-center">
                <DocumentTextIcon class="mx-auto h-12 w-12 text-slate-300" />
                <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum PMDA encontrado</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros ou crie um novo PMDA.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>

    <!-- Mobile: cards (fallback da tabela) -->
    <PmdaPlanosCards
      v-if="viewMode === 'table'"
      class="md:hidden"
      :planos="planos.data"
      :can-edit="can('pmda.planos.edit')"
      :can-copiar="can('pmda.planos.copiar')"
      @edit="(id) => router.visit(route('pmda.planos.edit', id))"
      @copiar="copiar"
    />

    <!-- Grade -->
    <PmdaPlanosCards
      v-else
      :planos="planos.data"
      :can-edit="can('pmda.planos.edit')"
      :can-copiar="can('pmda.planos.copiar')"
      @edit="(id) => router.visit(route('pmda.planos.edit', id))"
      @copiar="copiar"
    />

    <!-- Paginacao compartilhada -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="PMDA"
      @close="closeExportModal"
      @export="onExport"
    />

    <PmdaCreateModal :show="showCriar" :municipios="municipios" @close="showCriar = false" />
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePermissions } from '@/Composables/usePermissions';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import ViewModeToggle from '@/Components/Molecules/ViewModeToggle.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import { InboxArrowDownIcon } from '@heroicons/vue/24/outline';
import { moduleIcon } from '@/Support/moduleIcons';
import PmdaStatusBadge from '@/Components/Atoms/Pmda/PmdaStatusBadge.vue';
import PmdaStatisticsCards from '@/Components/Organisms/Pmda/PmdaStatisticsCards.vue';
import PmdaFiltersSection from '@/Components/Organisms/Pmda/PmdaFiltersSection.vue';
import PmdaCreateModal from '@/Components/Organisms/Pmda/PmdaCreateModal.vue';
import PmdaPlanosCards from '@/Components/Organisms/Pmda/PmdaPlanosCards.vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const viewMode = ref('table');
const showCriar = ref(false);

const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('pmda.planos.export');

function onExport(params) {
  handleExport(params, {
    status: filtros.status || undefined,
    municipio_id: filtros.municipio_id || undefined,
  });
}

const props = defineProps({
  planos: { type: Object, default: () => ({ data: [], meta: {} }) },
  filtros: { type: Object, default: () => ({}) },
  statistics: { type: Object, default: () => ({ total: 0, emEdicao: 0, emAnalise: 0, aprovados: 0 }) },
  statusOpcoes: { type: Array, default: () => [] },
  municipios: { type: Array, default: () => [] },
});

const pagination = computed(() => {
  const m = props.planos?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

function aplicar(f) {
  router.get(route('pmda.planos.index'), {
    buscar: f.buscar || undefined,
    status: f.status || undefined,
    municipio_id: f.municipio_id || undefined,
    data_inicio: f.data_inicio || undefined,
    data_fim: f.data_fim || undefined,
  }, { preserveState: true, replace: true });
}

function limpar() {
  router.get(route('pmda.planos.index'), {}, { preserveState: true, replace: true });
}

function irParaPagina(page) {
  router.get(route('pmda.planos.index'), {
    ...props.filtros,
    page,
  }, { preserveState: true, replace: true });
}

function copiar(id) {
  router.post(route('pmda.planos.copiar', id));
}

function formatDate(iso) {
  if (!iso) return '—';
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '—' : d.toLocaleDateString('pt-BR');
}

const filtros = reactive({ ...props.filtros });
</script>
