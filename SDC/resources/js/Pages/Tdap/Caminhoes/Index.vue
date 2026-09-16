<template>
  <Head title="TDAP - Caminhões" />

  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Frota e Vistorias"
      description="Caminhões-tanque com a situação de vistoria de cada veículo"
      :icon="TruckIcon"
      :icon-image="moduleIcon('tdap')"
    >
      <template #actions>
        <!-- O historico completo (uma linha por inspecao) deixou de ser item
             de menu: daqui, e como detalhe desta tela. -->
        <ActionButton
          action="history"
          module="tdap"
          resource="vistorias"
          :allowed="canVerVistoria"
          label="Histórico de vistorias"
          @click="router.visit(route('tdap.vistorias.index'))"
        />
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

    <!-- Cards por APTIDAO, e nao por `ativo`: a flag de cadastro dizia "132
         ativos" enquanto so 2 veiculos podiam rodar. Clicar filtra a lista. -->
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-5">
      <StatCard
        title="Total da frota"
        :value="estatisticas.total"
        :icon="TruckIcon"
        variant="info"
        clickable
        @click="filtrarPorVistoria('')"
      />
      <StatCard
        title="Aptos a operar"
        :value="estatisticas.aptos"
        :icon="CheckIcon"
        variant="success"
        subtitle="Vistoria vigente"
        clickable
        @click="filtrarPorVistoria('apto')"
      />
      <StatCard
        title="Vistoria vencida"
        :value="estatisticas.vistoria_vencida"
        :icon="ClockIcon"
        variant="warning"
        subtitle="Precisa renovar"
        clickable
        @click="filtrarPorVistoria('vencida')"
      />
      <StatCard
        title="Sem vistoria"
        :value="estatisticas.sem_vistoria"
        :icon="ClockIcon"
        variant="danger"
        subtitle="Nunca vistoriado"
        clickable
        @click="filtrarPorVistoria('sem_vistoria')"
      />
      <StatCard
        title="Capacidade total"
        :value="`${Number(estatisticas.capacidade_total_m3 || 0).toFixed(2)} m³`"
        :icon="TruckIcon"
        variant="info"
        :format-number="false"
        :subtitle="estatisticas.placas_duplicadas ? `${estatisticas.placas_duplicadas} placas duplicadas` : ''"
      />
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
        <ResponsiveTable
      :items="caminhoes.data"
      :mobile-fields="CAMPOS_MOBILE"
      :get-item-title="(caminhao) => caminhao.placa"
      empty-message="Nenhum caminhao encontrado"
    >
      <template #table>
        <table class="w-full text-sm">
                  <thead class="border-b border-slate-200 bg-slate-100 text-xs font-semibold uppercase text-slate-500 dark:border-slate-700/50 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                      <th class="px-4 py-3 text-left">Placa</th>
                      <th class="px-4 py-3 text-left">Prestador</th>
                      <th class="px-4 py-3 text-left">Marca / Modelo</th>
                      <th class="px-4 py-3 text-right">Capacidade (m³)</th>
                      <th class="px-4 py-3 text-left">Vistoria</th>
                      <th class="px-4 py-3 text-left">Status</th>
                      <th class="w-36 px-4 py-3 text-right">Ações</th>
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
                      <td class="px-4 py-4">
                        <VistoriaSituacaoBadge :situacao="caminhao.situacao_vistoria" :dias-restantes="caminhao.vistoria?.dias_restantes" />
                        <p v-if="caminhao.vistoria" class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                          {{ fmtDate(caminhao.vistoria.data) }}
                          <span v-if="caminhao.total_vistorias > 1" class="text-slate-400">· {{ caminhao.total_vistorias }} no histórico</span>
                        </p>
                      </td>
                      <td class="whitespace-nowrap px-4 py-4">
                        <TdapStatusBadge :active="caminhao.ativo" />
                      </td>
                      <td class="table-actions-cell px-4 py-4">
                        <div class="flex items-center justify-end">
                          <ActionButton module="tdap" resource="caminhoes" :actions="acoesDaLinha(caminhao)" />
                        </div>
                      </td>
                    </tr>
        
                    <tr v-if="caminhoes.data.length === 0">
                      <td colspan="7" class="px-4 py-10 text-center">
                        <TruckIcon class="mx-auto h-12 w-12 text-slate-400" />
                        <p class="mt-3 text-sm font-semibold text-slate-900 dark:text-slate-100">Nenhum caminhão encontrado</p>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ajuste os filtros ou cadastre um novo caminhão.</p>
                      </td>
                    </tr>
                  </tbody>
                </table>
      </template>

      <template #mobile-c1="{ item: caminhao }">
        <p class="font-semibold text-slate-900 dark:text-slate-100">{{ caminhao.prestador_nome }}</p>
        <p class="mt-0.5 font-mono text-xs text-slate-500 dark:text-slate-400">{{ caminhao.prestador_cnpj }}</p>
      </template>

      <template #mobile-c2="{ item: caminhao }">
        <span v-if="caminhao.marca || caminhao.modelo">
        {{ caminhao.marca }} {{ caminhao.modelo }}
        <span v-if="caminhao.ano" class="text-slate-400">({{ caminhao.ano }})</span>
        </span>
        <span v-else class="text-slate-400">-</span>
      </template>

      <template #mobile-c3="{ item: caminhao }">
        {{ Number(caminhao.capacidade_m3 || 0).toFixed(2) }}
      </template>

      <template #mobile-c4="{ item: caminhao }">
        <VistoriaSituacaoBadge :situacao="caminhao.situacao_vistoria" :dias-restantes="caminhao.vistoria?.dias_restantes" />
        <span v-if="caminhao.vistoria" class="ml-1 text-xs text-slate-500 dark:text-slate-400">
        {{ fmtDate(caminhao.vistoria.data) }}
        </span>
      </template>

      <template #mobile-c5="{ item: caminhao }">
        <TdapStatusBadge :active="caminhao.ativo" />
      </template>

      <template #mobile-actions="{ item: caminhao }">
        <div class="flex items-center justify-end">
        <ActionButton module="tdap" resource="caminhoes" :actions="acoesDaLinha(caminhao)" />
        </div>
      </template>
    </ResponsiveTable>
      </div>

    </div>

      <Pagination :pagination="caminhoes.meta" @page-change="irParaPagina" />

    <ExportCsvModal
      :show="showExportModal"
      module-name="Caminhoes"
      @close="closeExportModal"
      @export="onExport"
    />

    <!-- Exclusao passa por confirmacao, como no PAE: o caminhao pode estar
         alocado em cronograma vivo, e o servico recusa com mensagem de negocio
         -- sem o dialogo, o clique errado so aparecia depois do redirect. -->
    <ConfirmDialog
      :is-open="caminhaoParaExcluir !== null"
      variant="danger"
      title="Excluir caminhão"
      :message="`Excluir o caminhão ${caminhaoParaExcluir?.placa ?? ''}?`"
      description="O veículo sai da frota e das listagens. Se estiver alocado em cronograma ativo ou em rascunho, a exclusão será recusada."
      confirm-text="Excluir"
      :loading="excluindo"
      @confirm="confirmarExclusao"
      @cancel="caminhaoParaExcluir = null"
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
import { useExport } from '@/Composables/data/useExport';
import DownloadIcon from '@/Components/Icons/DownloadIcon.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import TdapCaminhoesFiltersSection from '@/Components/Organisms/Tdap/TdapCaminhoesFiltersSection.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ResponsiveTable from '@/Components/Organisms/Table/ResponsiveTable.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import VistoriaSituacaoBadge from '@/Components/Organisms/Tdap/VistoriaSituacaoBadge.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  caminhoes:    { type: Object, default: () => ({ data: [], meta: {} }) },
  estatisticas: { type: Object, default: () => ({ total: 0, ativos: 0, aptos: 0, vistoria_vencida: 0, sem_vistoria: 0, capacidade_total_m3: 0, placas_duplicadas: 0 }) },
  prestadores:  { type: Array, default: () => [] },
  filtros:      { type: Object, default: () => ({}) },
  canCreate:    { type: Boolean, default: false },
  canEdit:      { type: Boolean, default: false },
  canDelete:    { type: Boolean, default: false },
  canVerVistoria:   { type: Boolean, default: false },
  canCriarVistoria: { type: Boolean, default: false },
});

const activeFilters = ref({
  search: props.filtros.search ?? '',
  prestador_id: props.filtros.prestador_id ?? '',
  ativo: props.filtros.ativo ?? '',
  vistoria: props.filtros.vistoria ?? '',
});

/** Querystring a partir dos filtros, num lugar so (a paginacao usava outra). */
function queryDosFiltros(filters = activeFilters.value) {
  return {
    search:       filters.search || undefined,
    prestador_id: filters.prestador_id || undefined,
    ativo:        filters.ativo !== '' && filters.ativo !== undefined ? filters.ativo : undefined,
    vistoria:     filters.vistoria || undefined,
  };
}

function aplicarFiltros(filters = activeFilters.value) {
  router.get(route('tdap.caminhoes.index'), queryDosFiltros(filters), {
    preserveState: true,
    replace: true,
  });
}

function limparFiltros() {
  activeFilters.value = {};
  router.get(route('tdap.caminhoes.index'), {}, { preserveState: true, replace: true });
}

/**
 * Card de contagem como filtro.
 *
 * O numero em destaque e a pergunta ("quantos nao podem rodar?"); clicar nele
 * entrega a lista correspondente, sem obrigar o operador a abrir o bloco de
 * filtros e repetir o que ja apontou.
 */
function filtrarPorVistoria(situacao) {
  activeFilters.value = { ...activeFilters.value, vistoria: situacao };
  aplicarFiltros();
}

/**
 * Acoes da linha, no padrao de grupo do PAE.
 *
 * Duas ficam inline -- ver e editar, as que o operador usa a cada passagem --
 * e o resto vai para o menu de opcoes. Antes eram quatro icones soltos lado a
 * lado, sem rotulo: com a coluna de vistoria a linha ficou mais densa, e
 * quatro alvos iguais de 32px e onde se clica em "excluir" achando que era
 * "nova vistoria".
 *
 * Vistoria entra com `module`/`resource` proprios: a permissao dela nao e a do
 * caminhao, e o ActionButton filtra cada item pelo seu.
 */
function acoesDaLinha(caminhao) {
  return [
    { action: 'view', handler: () => router.visit(route('tdap.caminhoes.show', caminhao.id)) },
    {
      action: 'edit',
      allowed: props.canEdit,
      handler: () => router.visit(route('tdap.caminhoes.edit', caminhao.id)),
    },
    {
      action: 'history',
      placement: 'menu',
      label: 'Ver última vistoria',
      module: 'tdap',
      resource: 'vistorias',
      // Sem vistoria nenhuma nao ha o que abrir -- e o menu nao deve oferecer
      // um caminho que termina em 404.
      allowed: props.canVerVistoria && caminhao.vistoria !== null,
      handler: () => router.visit(route('tdap.vistorias.show', caminhao.vistoria.id)),
    },
    {
      action: 'create',
      placement: 'menu',
      label: 'Nova vistoria',
      module: 'tdap',
      resource: 'vistorias',
      allowed: props.canCriarVistoria,
      handler: () => router.visit(route('tdap.vistorias.create', { placa_id: caminhao.id })),
    },
    {
      action: 'delete',
      placement: 'menu',
      allowed: props.canDelete,
      handler: () => { caminhaoParaExcluir.value = caminhao; },
    },
  ];
}

const caminhaoParaExcluir = ref(null);
const excluindo = ref(false);

function confirmarExclusao() {
  if (caminhaoParaExcluir.value === null) return;

  excluindo.value = true;

  router.delete(route('tdap.caminhoes.destroy', caminhaoParaExcluir.value.id), {
    preserveScroll: true,
    onFinish: () => {
      excluindo.value = false;
      caminhaoParaExcluir.value = null;
    },
  });
}

function fmtDate(valor) {
  if (! valor) return '-';

  const [ano, mes, dia] = String(valor).slice(0, 10).split('-');

  return `${dia}/${mes}/${ano}`;
}

// Exportacao CSV (mesmo padrao do Cronograma)
const { showExportModal, openExportModal, closeExportModal, handleExport } = useExport('tdap.caminhoes.export');

function onExport(params) {
  handleExport(params, queryDosFiltros());
}

function irParaPagina(page) {
  router.get(route('tdap.caminhoes.index'), { ...queryDosFiltros(), page }, {
    preserveState: true,
    replace: true,
  });
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
  { key: 'c1', label: 'Prestador' },
  { key: 'c2', label: 'Marca / Modelo' },
  { key: 'c3', label: 'Capacidade (m³)' },
  { key: 'c4', label: 'Vistoria' },
  { key: 'c5', label: 'Status' },
];
</script>
