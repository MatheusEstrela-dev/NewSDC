<template>
  <Head title="TDAP — Vistorias" />
  <div class="p-6 space-y-6">
    <PageHeader variant="gradient"
      title="Vistorias de Veículos"
      description="Inspeções técnicas dos caminhões-tanque (vigência 12 meses)"
      :icon="TruckIcon"
    >
      <template #actions>
        <ActionButton action="export" :allowed="true" variant="success" label="Exportar" @click="openExportModal" />
        <Link v-if="canCreate" :href="route('tdap.vistorias.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Nova Vistoria</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="ClipboardDocumentListIcon" variant="info"
        clickable @click="filtrarRapido({})" />
      <StatCard title="Aprovadas" :value="estatisticas.aprovadas ?? 0" :icon="CheckBadgeIcon" variant="success"
        clickable @click="filtrarRapido({ parecer: 'aprovada' })" />
      <StatCard title="Vigentes" :value="estatisticas.vigentes ?? 0" :icon="ClockIcon" variant="info"
        clickable @click="filtrarRapido({ vigente: true })" />
      <StatCard title="Expiradas" :value="estatisticas.expiradas ?? 0" :icon="ExclamationTriangleIcon" variant="warning" />
      <StatCard title="Reprovadas" :value="estatisticas.reprovadas ?? 0" :icon="ExclamationIcon" variant="danger"
        clickable @click="filtrarRapido({ parecer: 'reprovada' })" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar"
        type="text"
        :model-value="filtroSearch"
        placeholder="Vistoriador, edital ou ficha"
        @update:model-value="filtroSearch = $event"
      />
      <FilterField
        label="Caminhão"
        type="select"
        :model-value="filtroCaminhao"
        :options="caminhaoOptions"
        @update:model-value="filtroCaminhao = $event"
      />
      <FilterField
        label="Parecer"
        type="select"
        :model-value="filtroParecer"
        :options="parecerOptions"
        @update:model-value="filtroParecer = $event"
      />
      <div class="flex items-end pb-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
          <input type="checkbox" v-model="filtroVigente" @change="aplicarFiltros" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
          Apenas vigentes
        </label>
      </div>
      <div class="md:col-span-2 lg:col-span-3 flex justify-end items-end pt-1">
        <FilterActions @search="aplicarFiltros" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Data</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Caminhão</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Vistoriador</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ficha</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Parecer</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Vigência</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="v in vistorias.data" :key="v.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3">{{ fmtDate(v.data) }}</td>
            <td class="px-4 py-3">
              <Link :href="route('tdap.vistorias.show', v.id)" class="font-mono font-semibold text-blue-600 hover:text-blue-800">{{ v.caminhao_placa }}</Link>
              <p v-if="v.caminhao_modelo" class="text-xs text-slate-500">{{ v.caminhao_modelo }}</p>
            </td>
            <td class="px-4 py-3">
              <p>{{ v.nome }}</p>
              <p v-if="v.prestador_nome" class="text-xs text-slate-500">{{ v.prestador_nome }}</p>
            </td>
            <td class="px-4 py-3 font-mono text-xs">{{ v.ficha || '—' }}</td>
            <td class="px-4 py-3">
              <span :class="v.parecer === 'aprovada'
                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300'"
                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
                {{ v.parecer_label }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span v-if="v.esta_vigente" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-blue-500"></span>
                Vigente
              </span>
              <span v-else class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                Expirada
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center justify-end gap-1">
                <ActionButton
                  action="view"
                  module="tdap"
                  resource="vistorias"
                  :allowed="true"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Visualizar vistoria"
                  @click="router.visit(route('tdap.vistorias.show', v.id))"
                />
                <ActionButton
                  action="edit"
                  module="tdap"
                  resource="vistorias"
                  :allowed="canEdit"
                  :show-label="false"
                  size="sm"
                  tooltip-text="Editar vistoria"
                  @click="router.visit(route('tdap.vistorias.edit', v.id))"
                />
              </div>
            </td>
          </tr>
          <tr v-if="vistorias.data.length === 0">
            <td colspan="7" class="px-4 py-12 text-center text-slate-400">Nenhuma vistoria cadastrada.</td>
          </tr>
        </tbody>
      </table>

    </div>

    <div v-if="vistorias.meta && vistorias.meta.last_page > 1" class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-3 dark:border-slate-700/50 dark:bg-slate-900/60">
      <Pagination :pagination="vistorias.meta" @page-change="irParaPagina" />
    </div>

    <ExportCsvModal
      :show="showExportModal"
      module-name="Vistorias"
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
import Button from '@/Components/Atoms/Button/Button.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { computed } from 'vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ExclamationIcon from '@/Components/Icons/ExclamationIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  vistorias:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total:0,aprovadas:0,reprovadas:0,vigentes:0,expiradas:0 }) },
  caminhoes:    { type: Array, default: () => [] },
  pareceres:    { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroSearch   = ref(props.filtros.search ?? '');
const filtroCaminhao = ref(props.filtros.placa_id ?? '');
const filtroParecer  = ref(props.filtros.parecer ?? '');
const filtroVigente  = ref(Boolean(props.filtros.vigente));

const caminhaoOptions = computed(() => [
  { value: '', label: 'Todos os caminhões' },
  ...props.caminhoes.map(c => ({ value: c.id, label: c.placa })),
]);
const parecerOptions = computed(() => [
  { value: '', label: 'Todos os pareceres' },
  ...props.pareceres.map(p => ({ value: p.value, label: p.label })),
]);

function limparFiltros() {
  filtroSearch.value = '';
  filtroCaminhao.value = '';
  filtroParecer.value = '';
  filtroVigente.value = false;
  router.get(route('tdap.vistorias.index'), {}, { preserveState: false });
}

function aplicarFiltros() {
  router.get(route('tdap.vistorias.index'), {
    search:   filtroSearch.value || undefined,
    placa_id: filtroCaminhao.value || undefined,
    parecer:  filtroParecer.value || undefined,
    vigente:  filtroVigente.value ? 1 : undefined,
  }, { preserveState: true, replace: true });
}

// Cards de estatistica como filtros rapidos: objeto vazio limpa parecer/vigente (Total),
// preservando os demais filtros (busca e caminhao), como no padrao PMDA.
function filtrarRapido({ parecer = '', vigente = false } = {}) {
  filtroParecer.value = parecer;
  filtroVigente.value = vigente;
  aplicarFiltros();
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.vistorias.export');

function onExport(params) {
  handleExport(params, {
    search:   filtroSearch.value || undefined,
    placa_id: filtroCaminhao.value || undefined,
    parecer:  filtroParecer.value || undefined,
    vigente:  filtroVigente.value ? 1 : undefined,
  });
}

function fmtDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}

function irParaPagina(page) {
  router.get(route('tdap.vistorias.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}
</script>
