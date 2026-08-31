<template>
  <Head title="TDAP — Atas" />
  <div class="w-full space-y-6 pb-8">
    <PageHeader variant="gradient"
      title="Atas de Registro de Preços"
      description="Contratos-pai que autorizam o fornecimento de água potável"
      :icon="CalendarIcon"
      :icon-image="moduleIcon('tdap')"
      :espaco-inferior="false"
    >
      <template #actions>
        <Button variant="success" size="md" :icon="DownloadIcon" icon-position="left" @click="openExportModal">
          <span class="hidden sm:inline">Exportar</span>
          <span class="sm:hidden">CSV</span>
        </Button>
        <Link v-if="canCreate" :href="route('tdap.atas.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span class="hidden sm:inline">Nova Ata</span>
            <span class="sm:hidden">Nova</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <!-- Cards funcionam como filtros rapidos; os tres ultimos filtram por situacao. -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="DocumentTextIcon" variant="info" clickable @click="limparSituacao()" />
      <StatCard title="Ativas" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" clickable @click="filtrarPorStatus({ ativo: '1' })" />
      <StatCard title="Vigentes" :value="estatisticas.vigentes ?? 0" :icon="CheckIcon" variant="success" clickable @click="filtrarPorSituacao('vigente')" />
      <StatCard title="A vencer (30d)" :value="estatisticas.a_vencer ?? 0" :icon="ClockIcon" variant="warning" clickable @click="filtrarPorSituacao('vigente')" />
      <StatCard title="Vencidas" :value="estatisticas.vencidas ?? estatisticas.encerradas ?? 0" :icon="ClockIcon" variant="danger" clickable @click="filtrarPorSituacao('vencida')" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar"
        type="text"
        :model-value="filtroSearch"
        placeholder="Por número ou histórico"
        @update:model-value="filtroSearch = $event"
      />
      <FilterField
        label="Status"
        type="select"
        :model-value="filtroAtivo"
        :options="ativoOptions"
        @update:model-value="filtroAtivo = $event"
      />
      <FilterField
        label="Situação (vigência)"
        type="select"
        :model-value="filtroSituacao"
        :options="situacaoSelectOptions"
        @update:model-value="filtroSituacao = $event"
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
      <ResponsiveTable
      :items="atas.data"
      :mobile-fields="CAMPOS_MOBILE"
      :get-item-title="(a) => a.numero"
      empty-message="Nenhuma ata encontrada"
    >
      <template #table>
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Número</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Vigência</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Lotes</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                  <tr v-for="a in atas.data" :key="a.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                    <td class="px-4 py-3 text-sm font-mono font-semibold text-slate-900 dark:text-slate-100">
                      <Link :href="route('tdap.atas.show', a.id)" class="hover:text-blue-600">{{ a.numero }}</Link>
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
                      {{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}
                    </td>
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">{{ a.lotes_count }}</td>
                    <!--
                      O badge apenas PINTA o que o backend classificou (a.situacao).
                      Antes havia regra de negocio aqui: `v-else-if="a.ativo"` pintava de
                      verde "Ativa" uma ata ja vencida, escondendo o vencimento.
                    -->
                    <td class="px-4 py-3 text-sm whitespace-nowrap">
                      <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                        :class="classesSituacao(a)"
                      >
                        {{ a.situacao_label ?? (a.vigente ? 'Vigente' : (a.ativo ? 'Ativa' : 'Inativa')) }}
                      </span>
                      <span
                        v-if="a.proxima_vencer"
                        class="ml-2 text-xs font-medium text-amber-600 dark:text-amber-400"
                        :title="`Vigência termina em ${formatDate(a.dt_final)}`"
                      >
                        {{ rotuloAlerta(a) }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-1">
                        <ActionButton
                          action="view"
                          module="tdap"
                          resource="atas"
                          :allowed="true"
                          :show-label="false"
                          size="sm"
                          tooltip-text="Visualizar ata"
                          @click="router.visit(route('tdap.atas.show', a.id))"
                        />
                        <ActionButton
                          v-if="canEdit"
                          action="edit"
                          module="tdap"
                          resource="atas"
                          :allowed="canEdit"
                          :show-label="false"
                          size="sm"
                          tooltip-text="Editar ata"
                          @click="router.visit(route('tdap.atas.edit', a.id))"
                        />
                      </div>
                    </td>
                  </tr>
                  <tr v-if="atas.data.length === 0">
                    <td colspan="5" class="px-4 py-12 text-center text-slate-400">Nenhuma ata cadastrada.</td>
                  </tr>
                </tbody>
              </table>
      </template>

      <template #mobile-c1="{ item: a }">
        {{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}
      </template>

      <template #mobile-c2="{ item: a }">
        {{ a.lotes_count }}
      </template>

      <template #mobile-c3="{ item: a }">
        <span
        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
        :class="classesSituacao(a)"
        >
        {{ a.situacao_label ?? (a.vigente ? 'Vigente' : (a.ativo ? 'Ativa' : 'Inativa')) }}
        </span>
        <span
        v-if="a.proxima_vencer"
        class="ml-2 text-xs font-medium text-amber-600 dark:text-amber-400"
        :title="`Vigência termina em ${formatDate(a.dt_final)}`"
        >
        {{ rotuloAlerta(a) }}
        </span>
      </template>

      <template #mobile-actions="{ item: a }">
        <div class="flex items-center justify-end gap-1">
        <ActionButton
        action="view"
        module="tdap"
        resource="atas"
        :allowed="true"
        :show-label="false"
        size="sm"
        tooltip-text="Visualizar ata"
        @click="router.visit(route('tdap.atas.show', a.id))"
        />
        <ActionButton
        v-if="canEdit"
        action="edit"
        module="tdap"
        resource="atas"
        :allowed="canEdit"
        :show-label="false"
        size="sm"
        tooltip-text="Editar ata"
        @click="router.visit(route('tdap.atas.edit', a.id))"
        />
        </div>
      </template>
    </ResponsiveTable>

    </div>

      <Pagination :pagination="atas.meta" @page-change="irParaPagina" />

    <ExportCsvModal
      :show="showExportModal"
      module-name="Atas"
      @close="closeExportModal"
      @export="onExport"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import ExportCsvModal from '@/Components/Organisms/ExportCsvModal.vue';
import { useExport } from '@/Composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  atas:            { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas:    { type: Object, default: () => ({ total: 0, ativos: 0, vigentes: 0, vencidas: 0, a_vencer: 0, encerradas: 0 }) },
  filtros:         { type: Object, default: () => ({}) },
  situacaoOptions: { type: Array, default: () => [] },
  canCreate:       { type: Boolean, default: false },
  canEdit:         { type: Boolean, default: false },
  canDelete:       { type: Boolean, default: false },
});

const filtroSearch = ref(props.filtros.search ?? '');
const filtroAtivo  = ref(props.filtros.ativo ?? '');
const filtroVigente = ref(Boolean(props.filtros.vigente));
const filtroSituacao = ref(props.filtros.situacao ?? '');

const ativoOptions = [
  { value: '', label: 'Todas' },
  { value: '1', label: 'Ativas' },
  { value: '0', label: 'Inativas' },
];

// A lista de situacoes vem do enum SituacaoAta (prop situacaoOptions), para nao
// duplicar no JS. O fallback cobre uma resposta antiga sem a prop.
const situacaoSelectOptions = computed(() => [
  { value: '', label: 'Todas' },
  ...(props.situacaoOptions.length
    ? props.situacaoOptions
    : [
        { value: 'vigente',  label: 'Vigente' },
        { value: 'vencida',  label: 'Vencida' },
        { value: 'agendada', label: 'Agendada' },
        { value: 'inativa',  label: 'Inativa' },
      ]),
]);

// Mapa token -> classes Tailwind. Escrito por extenso de proposito: string
// dinamica (`bg-${cor}-100`) e removida pelo purge do build.
const classesBadge = {
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  danger:  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  info:    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  neutral: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
};

function classesSituacao(ata) {
  return classesBadge[ata.situacao_cor] ?? classesBadge.neutral;
}

// "vence hoje" le melhor que "vence em 0d".
function rotuloAlerta(ata) {
  if (ata.dias_restantes === 0) return 'vence hoje';
  return `vence em ${ata.dias_restantes}d`;
}

// Monta a query string uma unica vez — usada pela busca, pelos cards e pelo CSV.
function paramsAtuais() {
  return {
    search:   filtroSearch.value || undefined,
    ativo:    filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
    vigente:  filtroVigente.value ? 1 : undefined,
    situacao: filtroSituacao.value || undefined,
  };
}

function aplicarFiltros() {
  router.get(route('tdap.atas.index'), paramsAtuais(), { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroSearch.value = '';
  filtroAtivo.value = '';
  filtroVigente.value = false;
  filtroSituacao.value = '';
  router.get(route('tdap.atas.index'), {}, { preserveState: false });
}

// Cards de estatistica como filtros rapidos, preservando a busca textual.
function filtrarPorStatus({ ativo, vigente }) {
  filtroAtivo.value = ativo ?? '';
  filtroVigente.value = Boolean(vigente);
  filtroSituacao.value = '';
  aplicarFiltros();
}

// Card de situacao: usa o filtro novo e zera os filtros antigos para nao
// combinar duas regras de vigencia na mesma consulta.
function filtrarPorSituacao(situacao) {
  filtroSituacao.value = situacao;
  filtroAtivo.value = '';
  filtroVigente.value = false;
  aplicarFiltros();
}

// Card "Total": limpa toda classificacao, mantendo so a busca textual.
function limparSituacao() {
  filtrarPorStatus({ ativo: '', vigente: false });
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.atas.export');

function onExport(params) {
  // O CSV respeita os mesmos filtros da tela, incluindo o de situacao.
  handleExport(params, paramsAtuais());
}

function formatDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
function irParaPagina(page) {
  router.get(route('tdap.atas.index'), { ...props.filtros, page }, { preserveState: true, replace: true });
}

/**
 * Campos do card no mobile (regra 9 de responsividade).
 *
 * Sao os que IDENTIFICAM o registro, nao todos: card com oito linhas nao e
 * melhor que tabela rolando de lado. Cada um reusa o markup da celula
 * original pelo slot `#mobile-<key>`, entao badge e formatacao continuam
 * identicos aos da tabela.
 */
const CAMPOS_MOBILE = [
  { key: 'c1', label: 'Vigência' },
  { key: 'c2', label: 'Lotes' },
  { key: 'c3', label: 'Status' },
];
</script>
