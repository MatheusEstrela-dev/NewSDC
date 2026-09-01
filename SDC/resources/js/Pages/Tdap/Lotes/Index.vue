<template>
  <Head title="TDAP — Lotes" />
  <div class="w-full space-y-6 pb-8">
    <PageHeader variant="gradient"
      title="Lotes de Fornecimento"
      description="Subdivisões das atas por município e prestador"
      :icon="MapIcon"
      :icon-image="moduleIcon('tdap')"
      :espaco-inferior="false"
    >
      <template #actions>
        <Button variant="success" size="md" :icon="DownloadIcon" icon-position="left" @click="openExportModal">
          <span class="hidden sm:inline">Exportar</span>
        </Button>
        <Link v-if="canCreate" :href="route('tdap.lotes.create')">
          <Button variant="primary" size="md" :icon="PlusIcon" icon-position="left">
            <span>Novo Lote</span>
          </Button>
        </Link>
      </template>
    </PageHeader>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <StatCard title="Total" :value="estatisticas.total ?? 0" :icon="DocumentIcon" variant="info" clickable @click="filtrarPorAtivo('')" />
      <StatCard title="Ativos" :value="estatisticas.ativos ?? 0" :icon="CheckCircleIcon" variant="success" clickable @click="filtrarPorAtivo('1')" />
      <StatCard title="Volume contratado (m³)" :value="Number(estatisticas.volume_total_m3 || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2})" :icon="CubeIcon" variant="info" :format-number="false" />
      <StatCard title="Valor total (R$)" :value="Number(estatisticas.valor_total || 0).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2})" :icon="CheckBadgeIcon" variant="info" :format-number="false" />
    </div>

    <FilterSection title="Filtros de Pesquisa" :columns="3" :default-collapsed="true">
      <FilterField
        label="Buscar"
        type="search"
        placeholder="Número, nome ou contrato"
        :model-value="filtroBusca"
        @update:model-value="filtroBusca = $event"
      />
      <FilterField
        label="Ata"
        type="select"
        :model-value="filtroAta"
        :options="ataOptions"
        @update:model-value="filtroAta = $event"
      />
      <FilterField
        label="Município"
        type="select"
        :model-value="filtroMunicipio"
        :options="municipioOptions"
        @update:model-value="filtroMunicipio = $event"
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
      <ResponsiveTable
      :items="lotes.data"
      :mobile-fields="CAMPOS_MOBILE"
      :get-item-title="(l) => l.numero"
      :get-item-subtitle="(l) => l.nome"
      empty-message="Nenhum lote encontrado"
    >
      <template #table>
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Lote</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ata</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Municípios</th>
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
                      <span v-if="l.contrato" class="block text-xs text-slate-400">Contrato {{ l.contrato }}</span>
                    </td>
                    <td class="px-4 py-3 text-sm font-mono text-slate-700 dark:text-slate-300">{{ l.ata_numero }}</td>
                    <!--
                      Um lote atende varios municipios (relacao N:N). Listar todos em
                      chips estourava a linha nos lotes grandes (ha lote com mais de 30
                      municipios): a celula mostra os primeiros, um contador do resto e
                      a lista inteira no title/no detalhe do lote.
                    -->
                    <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300 align-top">
                      <div v-if="municipiosDo(l).length" class="max-w-xs" :title="listaMunicipios(l)">
                        <div class="flex flex-wrap items-center gap-1">
                          <span
                            v-for="m in municipiosDo(l).slice(0, MUNICIPIOS_VISIVEIS)"
                            :key="m.id"
                            class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300"
                          >
                            {{ m.nome }}<span v-if="m.uf" class="text-slate-400">/{{ m.uf }}</span>
                          </span>
                          <Link
                            v-if="municipiosDo(l).length > MUNICIPIOS_VISIVEIS"
                            :href="route('tdap.lotes.show', l.id)"
                            class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-900/30 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100"
                          >
                            +{{ municipiosDo(l).length - MUNICIPIOS_VISIVEIS }}
                          </Link>
                        </div>
                        <span class="mt-1 block text-xs text-slate-400">
                          {{ municipiosDo(l).length }} município(s)
                        </span>
                      </div>
                      <span v-else class="text-slate-400">—</span>
                    </td>
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
                        <ActionButton
                          v-if="canDelete"
                          action="delete"
                          module="tdap"
                          resource="lotes"
                          :allowed="canDelete"
                          :show-label="false"
                          size="sm"
                          tooltip-text="Excluir lote"
                          @click="excluir(l)"
                        />
                      </div>
                    </td>
                  </tr>
                  <tr v-if="lotes.data.length === 0">
                    <td colspan="8" class="px-4 py-12 text-center text-slate-400">Nenhum lote cadastrado.</td>
                  </tr>
                </tbody>
              </table>
      </template>

      <template #mobile-c2="{ item: l }">
        <div v-if="municipiosDo(l).length" class="max-w-xs" :title="listaMunicipios(l)">
        <div class="flex flex-wrap items-center gap-1">
        <span
        v-for="m in municipiosDo(l).slice(0, MUNICIPIOS_VISIVEIS)"
        :key="m.id"
        class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-xs text-slate-700 dark:text-slate-300"
        >
        {{ m.nome }}<span v-if="m.uf" class="text-slate-400">/{{ m.uf }}</span>
        </span>
        <Link
        v-if="municipiosDo(l).length > MUNICIPIOS_VISIVEIS"
        :href="route('tdap.lotes.show', l.id)"
        class="inline-flex items-center px-2 py-0.5 rounded-md bg-blue-50 dark:bg-blue-900/30 text-xs font-medium text-blue-700 dark:text-blue-300 hover:bg-blue-100"
        >
        +{{ municipiosDo(l).length - MUNICIPIOS_VISIVEIS }}
        </Link>
        </div>
        <span class="mt-1 block text-xs text-slate-400">
        {{ municipiosDo(l).length }} município(s)
        </span>
        </div>
        <span v-else class="text-slate-400">—</span>
      </template>

      <template #mobile-c3="{ item: l }">
        {{ l.prestador_nome }}
      </template>

      <template #mobile-c4="{ item: l }">
        {{ Number(l.qtd_agua_m3).toLocaleString('pt-BR', {minimumFractionDigits:2,maximumFractionDigits:2}) }}
      </template>

      <template #mobile-c6="{ item: l }">
        <span :class="l.ativo ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'" class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium">
        {{ l.ativo ? 'Ativo' : 'Inativo' }}
        </span>
      </template>

      <template #mobile-actions="{ item: l }">
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
        <ActionButton
        v-if="canDelete"
        action="delete"
        module="tdap"
        resource="lotes"
        :allowed="canDelete"
        :show-label="false"
        size="sm"
        tooltip-text="Excluir lote"
        @click="excluir(l)"
        />
        </div>
      </template>
    </ResponsiveTable>

    </div>

      <Pagination :pagination="lotes.meta" @page-change="irParaPagina" />

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
import MapIcon from '@/Components/Icons/MapIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import { computed } from 'vue';
import CheckBadgeIcon from '@/Components/Icons/CheckBadgeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import DocumentIcon from '@/Components/Icons/DocumentIcon.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  lotes:        { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, volume_total_m3: 0, valor_total: 0 }) },
  atas:         { type: Array, default: () => [] },
  municipios:   { type: Array, default: () => [] },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
});

const filtroBusca     = ref(props.filtros.search ?? '');
const filtroAta       = ref(props.filtros.ata_id ?? '');
const filtroMunicipio = ref(props.filtros.municipio_id ?? '');
const filtroPrestador = ref(props.filtros.prestador_id ?? '');
const filtroAtivo     = ref(props.filtros.ativo ?? '');

const ataOptions = computed(() => [
  { value: '', label: 'Todas as atas' },
  ...props.atas.map(a => ({ value: a.id, label: a.numero })),
]);
// Vem do backend ja restrito aos municipios que possuem lote no recorte atual.
const municipioOptions = computed(() => [
  { value: '', label: 'Todos os municípios' },
  ...props.municipios.map(m => ({ value: m.id, label: m.uf ? `${m.nome} / ${m.uf}` : m.nome })),
]);
const prestadorOptions = computed(() => [
  { value: '', label: 'Todos os prestadores' },
  ...props.prestadores.map(p => ({ value: p.id, label: p.nome })),
]);
// Quantos chips de municipio a celula mostra antes de resumir no contador.
const MUNICIPIOS_VISIVEIS = 3;

// A lista N:N so vem do backend quando a relacao esta carregada; o fallback
// evita quebrar a grade se o payload chegar sem ela.
function municipiosDo(lote) {
  return Array.isArray(lote?.municipios) ? lote.municipios : [];
}

// Lista completa no tooltip da celula — os chips resumidos escondem o resto.
function listaMunicipios(lote) {
  return municipiosDo(lote)
    .map(m => (m.uf ? `${m.nome}/${m.uf}` : m.nome))
    .join(', ');
}

const ativoOptions = [
  { value: '', label: 'Todos' },
  { value: '1', label: 'Ativos' },
  { value: '0', label: 'Inativos' },
];

// Fonte unica dos parametros de filtro: busca, cards, paginacao e export usam
// este objeto (antes cada um montava o seu e o municipio ficava de fora).
function queryFiltros() {
  return {
    search:       filtroBusca.value || undefined,
    ata_id:       filtroAta.value || undefined,
    municipio_id: filtroMunicipio.value || undefined,
    prestador_id: filtroPrestador.value || undefined,
    ativo:        filtroAtivo.value !== '' ? filtroAtivo.value : undefined,
  };
}

function aplicarFiltros() {
  router.get(route('tdap.lotes.index'), queryFiltros(), { preserveState: true, replace: true });
}

function limparFiltros() {
  filtroBusca.value = '';
  filtroAta.value = '';
  filtroMunicipio.value = '';
  filtroPrestador.value = '';
  filtroAtivo.value = '';
  router.get(route('tdap.lotes.index'), {}, { preserveState: false });
}

// Cards de estatistica como filtros rapidos: '' = Total (limpa o filtro de status), preservando ata/prestador.
function filtrarPorAtivo(ativo) {
  filtroAtivo.value = ativo ?? '';
  router.get(route('tdap.lotes.index'), queryFiltros(), { preserveState: true, replace: true });
}

// O backend recusa lote com cronograma vinculado e devolve flash de erro na
// tela do lote — aqui so confirmamos a intencao.
function excluir(lote) {
  if (!confirm(`Excluir o lote ${lote.numero}?`)) return;
  router.delete(route('tdap.lotes.destroy', lote.id), { preserveScroll: true });
}

// Exportacao CSV (mesmo padrao dos outros modulos)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.lotes.export');

function onExport(params) {
  handleExport(params, queryFiltros());
}
function irParaPagina(page) {
  router.get(route('tdap.lotes.index'), { ...queryFiltros(), page }, { preserveState: true, replace: true });
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
  { key: 'c2', label: 'Municípios' },
  { key: 'c3', label: 'Prestador' },
  { key: 'c4', label: 'Volume (m³)' },
  { key: 'c6', label: 'Status' },
];
</script>
