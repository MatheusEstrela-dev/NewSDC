<template>
  <Head title="TDAP — Cronogramas" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Cronogramas de Fornecimento"
      description="Ordens operacionais de entrega de água potável"
      :icon="TruckIcon"
    >
      <template #actions>
        <Button variant="success" size="md" :icon="DownloadIcon" icon-position="left" @click="openExportModal">
          <span class="hidden sm:inline">Exportar</span>
          <span class="sm:hidden">CSV</span>
        </Button>
        <Link v-if="canCreate" :href="route('tdap.cronogramas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Novo Cronograma</span>
            <span class="sm:hidden">Novo</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="ClipboardDocumentListIcon" variant="info" clickable @click="filtrarPorEstado('')" />
      <StatCard title="Ativos" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" clickable @click="filtrarPorEstado('ativo')" />
      <StatCard title="Rascunhos" :value="estatisticas.rascunhos ?? 0" :icon="DocumentTextIcon" variant="warning" clickable @click="filtrarPorEstado('rascunho')" />
      <StatCard title="Encerrados" :value="estatisticas.encerrados ?? 0" :icon="CheckIcon" variant="info" clickable @click="filtrarPorEstado('encerrado')" />
      <StatCard title="Volume ativo (m³)" :value="Number(estatisticas.volume_ativo_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:0,maximumFractionDigits:0})" :icon="CubeIcon" variant="info" :format-number="false" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar"
        type="text"
        :model-value="filtroSearch"
        placeholder="Número ou empenho"
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
        label="Ata"
        type="select"
        :model-value="filtroAta"
        :options="ataOptions"
        @update:model-value="filtroAta = $event"
      />
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Número</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Vigência</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ata / Lote</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Município / Prestador</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Volume (m³)</th>
            <th class="px-4 py-3 text-center text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Caminhões</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Estado</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="c in cronogramas.data" :key="c.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 text-sm font-mono">
              <Link :href="route('tdap.cronogramas.show', c.id)" class="text-blue-600 hover:text-blue-800 font-semibold">{{ c.numero }}</Link>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ fmtDate(c.dt_inicio) }} — {{ fmtDate(c.dt_final) }}</td>
            <td class="px-4 py-3 text-sm">
              <p class="font-mono">{{ c.ata_numero }}</p>
              <p class="text-xs text-slate-500 font-mono">{{ c.lote_numero }}</p>
            </td>
            <td class="px-4 py-3 text-sm">
              <p>{{ c.municipio_nome }}<span v-if="c.municipio_uf" class="text-slate-400">/{{ c.municipio_uf }}</span></p>
              <p class="text-xs text-slate-500">{{ c.prestador_nome }}</p>
            </td>
            <td class="px-4 py-3 text-sm text-right font-mono">{{ Number(c.volume_contratado_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}</td>
            <td class="px-4 py-3 text-sm text-center">{{ c.caminhoes_count }}</td>
            <td class="px-4 py-3 text-sm">
              <EstadoBadge :estado="c.estado" />
            </td>
            <td class="px-4 py-3 text-right">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="view"
                  module="tdap"
                  resource="cronogramas"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Visualizar cronograma"
                  @click="router.visit(route('tdap.cronogramas.show', c.id))"
                />
                <ActionButton
                  v-if="c.estado === 'rascunho'"
                  action="edit"
                  module="tdap"
                  resource="cronogramas"
                  :allowed="canEdit"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar cronograma"
                  @click="router.visit(route('tdap.cronogramas.edit', c.id))"
                />
                <ActionButton
                  v-if="c.estado === 'rascunho'"
                  action="delete"
                  module="tdap"
                  resource="cronogramas"
                  :allowed="canDelete"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Excluir cronograma"
                  @click="excluir(c)"
                />
                <ActionButton
                  action="history"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Histórico"
                  @click="abrirHistorico(c)"
                />
                <ActionButton
                  action="archive"
                  :allowed="canDelete"
                  :show-label="false"
                  size="sm"
                  :tooltip-text="c.arquivado ? 'Desarquivar' : 'Arquivar'"
                  @click="arquivarToggle(c)"
                />
              </div>
            </td>
          </tr>
          <tr v-if="cronogramas.data.length === 0">
            <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum cronograma cadastrado.</td>
          </tr>
        </tbody>
      </table>

    </div>

    <div v-if="cronogramas.meta && cronogramas.meta.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="cronogramas.meta" @page-change="irParaPagina" />
    </div>

    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Excluir Cronograma"
      :message="`Tem certeza que deseja excluir o cronograma ${cronogramaToDelete?.numero ?? ''}?`"
      description="Esta acao ira marcar o cronograma como excluido. Os dados serao preservados para auditoria."
      variant="danger"
      confirm-text="Excluir"
      cancel-text="Cancelar"
      :loading="deleteLoading"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />

    <ConfirmDialog
      :is-open="showArchiveConfirm"
      :title="cronogramaToArchive?.arquivado ? 'Desarquivar Cronograma' : 'Arquivar Cronograma'"
      :message="`Deseja ${cronogramaToArchive?.arquivado ? 'desarquivar' : 'arquivar'} o cronograma ${cronogramaToArchive?.numero ?? ''}?`"
      description="Arquivar nao exclui o cronograma: ele apenas sai da listagem padrao e pode ser restaurado a qualquer momento."
      :variant="cronogramaToArchive?.arquivado ? 'info' : 'warning'"
      :confirm-text="cronogramaToArchive?.arquivado ? 'Desarquivar' : 'Arquivar'"
      cancel-text="Cancelar"
      :loading="archiveLoading"
      @confirm="confirmArchive"
      @cancel="cancelArchive"
    />

    <CronogramaHistoricoModal
      :open="historicoOpen"
      :numero="historicoNumero"
      :timeline="historicoTimeline"
      :loading="historicoLoading"
      @close="historicoOpen = false"
    />

    <ExportCsvModal
      :show="showExportModal"
      module-name="Cronogramas"
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
import EstadoBadge from '@/Components/Organisms/Tdap/EstadoBadge.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import CronogramaHistoricoModal from '@/Components/Organisms/Tdap/CronogramaHistoricoModal.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { computed } from 'vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineOptions({ layout: AuthenticatedLayout });

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

const filtroSearch = ref(props.filtros.search ?? '');
const filtroEstado = ref(props.filtros.estado ?? '');
const filtroAta    = ref(props.filtros.ata_id ?? '');

const estadoOptions = [
  { value: '', label: 'Todos' },
  { value: 'rascunho', label: 'Rascunho' },
  { value: 'ativo', label: 'Ativo' },
  { value: 'encerrado', label: 'Encerrado' },
  { value: 'arquivado', label: 'Arquivado' },
];
const ataOptions = computed(() => [
  { value: '', label: 'Todas' },
  ...props.atas.map(a => ({ value: a.id, label: a.numero })),
]);

function aplicarFiltros() {
  router.get(route('tdap.cronogramas.index'), {
    search: filtroSearch.value || undefined,
    estado: filtroEstado.value || undefined,
    ata_id: filtroAta.value || undefined,
  }, { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroSearch.value = '';
  filtroEstado.value = '';
  filtroAta.value = '';
  router.get(route('tdap.cronogramas.index'), {}, { preserveState: false });
}

// Cards de estatistica como filtros rapidos: '' = Total (limpa o filtro de estado), preservando os demais filtros.
function filtrarPorEstado(estado) {
  filtroEstado.value = estado || '';
  router.get(route('tdap.cronogramas.index'), {
    search: filtroSearch.value || undefined,
    estado: estado || undefined,
    ata_id: filtroAta.value || undefined,
  }, { preserveState: true, replace: true });
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.cronogramas.export');

function onExport(params) {
  handleExport(params, {
    search: filtroSearch.value || undefined,
    estado: filtroEstado.value || undefined,
    ata_id: filtroAta.value || undefined,
  });
}

const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const cronogramaToDelete = ref(null);

function excluir(c) {
  cronogramaToDelete.value = c;
  showDeleteConfirm.value = true;
}

function confirmDelete() {
  if (!cronogramaToDelete.value) return;
  router.delete(route('tdap.cronogramas.destroy', cronogramaToDelete.value.id), {
    preserveScroll: true,
    onStart: () => { deleteLoading.value = true; },
    onFinish: () => { deleteLoading.value = false; },
    onSuccess: () => { showDeleteConfirm.value = false; cronogramaToDelete.value = null; },
  });
}

function cancelDelete() {
  showDeleteConfirm.value = false;
  cronogramaToDelete.value = null;
}

// Histórico / timeline (reusa endpoint generico tdap.historicos.por_entidade)
const EVENTO_LABELS = {
  'cronograma.criado':       'Cronograma criado',
  'cronograma.ativado':      'Cronograma ativado',
  'cronograma.encerrado':    'Cronograma encerrado',
  'cronograma.prorrogado':   'Cronograma prorrogado',
  'cronograma.arquivado':    'Cronograma arquivado',
  'cronograma.desarquivado': 'Cronograma desarquivado',
};

function tipoCategoria(tipoEvento) {
  if (tipoEvento?.includes('criado')) return 'criacao';
  if (tipoEvento?.includes('arquiv')) return 'arquivo';
  return 'edicao';
}

function fmtDateTime(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString('pt-BR');
}

const historicoOpen = ref(false);
const historicoLoading = ref(false);
const historicoNumero = ref('');
const historicoTimeline = ref([]);

async function abrirHistorico(c) {
  historicoNumero.value = c.numero;
  historicoTimeline.value = [];
  historicoOpen.value = true;
  historicoLoading.value = true;
  try {
    const url = `${route('tdap.historicos.por_entidade')}?entity_type=cronograma&entity_id=${c.id}`;
    const res = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    if (res.ok) {
      const json = await res.json();
      historicoTimeline.value = (json?.data ?? []).map((h) => ({
        id:          h.id,
        tipo:        tipoCategoria(h.tipo_evento),
        titulo:      EVENTO_LABELS[h.tipo_evento] ?? h.tipo_evento,
        descricao:   h.obs ?? '',
        data:        fmtDateTime(h.data_evento),
        responsavel: h.user?.name ?? null,
      }));
    }
  } catch (_) { /* silencioso: timeline vazia */ }
  historicoLoading.value = false;
}

// Arquivar / desarquivar (distinto do soft delete)
const showArchiveConfirm = ref(false);
const archiveLoading = ref(false);
const cronogramaToArchive = ref(null);

function arquivarToggle(c) {
  cronogramaToArchive.value = c;
  showArchiveConfirm.value = true;
}

function confirmArchive() {
  const c = cronogramaToArchive.value;
  if (!c) return;
  const routeName = c.arquivado ? 'tdap.cronogramas.desarquivar' : 'tdap.cronogramas.arquivar';
  router.patch(route(routeName, c.id), {}, {
    preserveScroll: true,
    onStart: () => { archiveLoading.value = true; },
    onFinish: () => { archiveLoading.value = false; },
    onSuccess: () => { showArchiveConfirm.value = false; cronogramaToArchive.value = null; },
  });
}

function cancelArchive() {
  showArchiveConfirm.value = false;
  cronogramaToArchive.value = null;
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
function irParaPagina(page) {
  router.get(route('tdap.cronogramas.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}
</script>
