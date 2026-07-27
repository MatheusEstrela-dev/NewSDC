<template>
  <div class="legado-rat-index">
    <PageHeader
      title="RAT — Arquivo Morto"
      description="Registros legados de Atendimento Tecnico (somente leitura)"
      :icon="ArchiveBoxIcon"
      :icon-image="moduleIcon('rat')"
      variant="gradient"
    />

    <!-- Stat cards = filtros rapidos (padrao do projeto) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
      <StatCard
        title="Total de RATs"
        :value="statistics.total"
        :icon="DocumentTextIcon"
        variant="info"
        clickable
        @click="limparFiltros"
      />
      <StatCard
        title="Municipios"
        :value="statistics.municipios"
        :icon="MapPinIcon"
        variant="success"
      />
      <StatCard
        :title="`Este Ano (${anoAtual})`"
        :value="statistics.esteAno"
        :icon="CalendarIcon"
        variant="warning"
        clickable
        @click="filtrarAnoAtual"
      />
    </div>

    <!-- Filtros (FilterSection padrao) -->
    <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
      <FilterField
        label="Buscar"
        type="search"
        :model-value="local.search"
        placeholder="Numero, envolvidos, operacao ou local..."
        class="lg:col-span-2"
        @update:model-value="local.search = $event"
      />
      <FilterField
        label="Municipio"
        type="select"
        :model-value="local.municipio_id"
        :options="municipioOptions"
        placeholder="Todos"
        @update:model-value="local.municipio_id = $event"
      />
      <FilterField
        label="Ano"
        type="select"
        :model-value="local.ano"
        :options="filterOptions.anos"
        placeholder="Todos"
        @update:model-value="local.ano = $event"
      />
      <FilterField
        label="Tipo de ocorrencia"
        type="select"
        :model-value="local.tipo_id"
        :options="tipoOptions"
        placeholder="Todos"
        class="lg:col-span-3"
        @update:model-value="local.tipo_id = $event"
      />
      <div class="flex justify-end items-end">
        <FilterActions @search="aplicar" @clear="limparFiltros" />
      </div>
    </FilterSection>

    <!-- Lista -->
    <CardBase variant="default" padding="none" class="overflow-hidden">
      <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
        <DocumentTextIcon class="w-5 h-5 text-slate-400" />
        <Heading :level="5" class="!mb-0">
          Lista de RATs
          <span class="text-slate-400 font-normal">({{ formatNumber(pagination?.total ?? rats.length) }} registros)</span>
        </Heading>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 dark:bg-slate-900/40 text-slate-500 dark:text-slate-400 uppercase text-xs">
            <tr>
              <th class="th">Numero</th>
              <th class="th">Data</th>
              <th class="th">Municipio</th>
              <th class="th">Tipo</th>
              <th class="th">Operador</th>
              <th class="th text-right">Acoes</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
            <tr v-for="rat in rats" :key="rat.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/40">
              <td class="td font-medium text-blue-600 dark:text-blue-400">
                <Link :href="route('rat.arquivados.show', rat.id)">{{ rat.num_ocorrencia }}</Link>
              </td>
              <td class="td whitespace-nowrap">{{ formatDate(rat.dt_ocorrencia) }}</td>
              <td class="td">{{ rat.municipio }}</td>
              <td class="td">{{ rat.tipo }}</td>
              <td class="td">{{ rat.operador }}</td>
              <td class="td">
                <div class="flex justify-end">
                  <ActionButton :actions="acoesDoRat(rat)" size="sm" />
                </div>
              </td>
            </tr>
            <tr v-if="rats.length === 0">
              <td colspan="6" class="td text-center text-slate-400 py-10">Nenhum registro encontrado.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </CardBase>

    <div v-if="pagination" class="mt-6">
      <Pagination :pagination="pagination" @page-change="irParaPagina" />
    </div>
  </div>
</template>

<script setup>
import ActionButton from '@/Components/Atoms/Button/ActionButton.vue';
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { ArchiveBoxIcon, CalendarIcon, DocumentTextIcon, MapPinIcon } from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const props = defineProps({
  rats:          { type: Array, default: () => [] },
  pagination:    { type: Object, default: null },
  statistics:    { type: Object, default: () => ({ total: 0, municipios: 0, esteAno: 0 }) },
  filterOptions: { type: Object, default: () => ({ municipios: [], tipos: [], anos: [] }) },
  filters:       { type: Object, default: () => ({}) },
});

const anoAtual = new Date().getFullYear();

const local = reactive({
  search:       props.filters.search ?? '',
  municipio_id: props.filters.municipio_id ?? '',
  tipo_id:      props.filters.tipo_id ?? '',
  ano:          props.filters.ano ?? '',
});

const municipioOptions = computed(() =>
  (props.filterOptions.municipios ?? []).map((m) => ({ value: m.id, label: m.nome })),
);
const tipoOptions = computed(() =>
  (props.filterOptions.tipos ?? []).map((t) => ({ value: t.id, label: t.descricao })),
);

function params(extra = {}) {
  const p = { ...extra };
  if (local.search) p.search = local.search;
  if (local.municipio_id) p.municipio_id = local.municipio_id;
  if (local.tipo_id) p.tipo_id = local.tipo_id;
  if (local.ano) p.ano = local.ano;
  return p;
}

function navegar(extra = {}) {
  router.get(route('rat.arquivados.index'), params(extra), {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function aplicar() {
  navegar();
}

function limparFiltros() {
  local.search = '';
  local.municipio_id = '';
  local.tipo_id = '';
  local.ano = '';
  navegar();
}

function filtrarAnoAtual() {
  local.ano = String(anoAtual);
  navegar();
}

function irParaPagina(page) {
  navegar({ page });
}

function acoesDoRat(rat) {
  return [
    {
      action: 'view',
      module: 'rat',
      resource: 'arquivados',
      handler: () => router.visit(route('rat.arquivados.show', rat.id)),
    },
    {
      action: 'print',
      module: 'rat',
      resource: 'arquivados',
      aliasOverride: 'view',
      handler: () => window.open(route('rat.arquivados.print', rat.id), '_blank'),
    },
  ];
}

function formatNumber(n) {
  return new Intl.NumberFormat('pt-BR').format(Number(n ?? 0));
}

function formatDate(iso) {
  if (!iso) return 'Nao informado';
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
  });
}
</script>

<style scoped>
.th { padding: .625rem 1rem; text-align: left; font-weight: 600; }
.td { padding: .625rem 1rem; }
</style>
