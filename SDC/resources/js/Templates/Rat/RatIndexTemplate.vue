<template>
  <div class="rat-index-container">
    <RatPageHeader />
    <RatStatisticsCards :statistics="statisticsToUse" />
    <RatFiltersSection
      :filters="filtersToUse"
      :municipalities="municipalities"
      :cobrade-types="cobradeTypes"
      :years="years"
      @filter-change="handleFilterChange"
      @filter-reset="handleFilterReset"
    />
    <RatTable
      :rats="ratsToUse"
      :loading="loading"
      :pagination="paginationToUse"
      @view="handleView"
      @edit="handleEdit"
      @attachments="handleAttachments"
      @delete="handleDelete"
      @page-change="handlePageChange"
    />
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import RatPageHeader from '../../Components/Organisms/Rat/Header/RatPageHeader.vue';
import RatStatisticsCards from '../../Components/Organisms/Rat/Statistics/RatStatisticsCards.vue';
import RatFiltersSection from '../../Components/Organisms/Rat/Filters/RatFiltersSection.vue';
import RatTable from '../../Components/Organisms/Rat/Table/RatTable.vue';
import { getMockStatisticsFromRats } from '@/mocks/rat';

const props = defineProps({
  statistics: {
    type: Object,
    required: true,
  },
  rats: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  pagination: {
    type: Object,
    default: null,
  },
  municipalities: {
    type: Array,
    default: () => [],
  },
  cobradeTypes: {
    type: Array,
    default: () => [],
  },
  years: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  useMock: {
    type: Boolean,
    default: false,
  },
});

// =========================
// Frontend-only behavior
// =========================
const perPage = 15;
const currentPage = ref(1);
const localFilters = ref({ ...(props.filters || {}) });

watch(
  () => props.filters,
  (next) => {
    if (!props.useMock) return;
    localFilters.value = { ...(next || {}) };
  },
  { deep: true }
);

function normalize(s) {
  return String(s || '').toLowerCase().trim();
}

function parseDateSafe(value) {
  if (!value) return null;
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? null : d;
}

function getYearFromCreatedAt(createdAt) {
  const d = parseDateSafe(createdAt);
  return d ? d.getFullYear() : null;
}

function matchesFilters(rat, f) {
  const protocolo = normalize(f?.protocolo);
  const status = normalize(f?.status);
  const municipio = normalize(f?.municipio);
  const ano = normalize(f?.ano);

  if (protocolo && !normalize(rat?.protocolo).includes(protocolo)) return false;
  if (status && normalize(rat?.status) !== status) return false;
  if (municipio && normalize(rat?.local?.municipio) !== municipio) return false;

  if (ano) {
    const y = getYearFromCreatedAt(rat?.created_at);
    if (!y || String(y) !== ano) return false;
  }

  const start = parseDateSafe(f?.data_inicio);
  const end = parseDateSafe(f?.data_fim);
  if (start || end) {
    const created = parseDateSafe(rat?.created_at);
    if (!created) return false;
    if (start && created < start) return false;
    if (end && created > end) return false;
  }

  // (natureza/tipo_cobrade/criado_por) ainda não existem no mock, então ignoramos por enquanto
  return true;
}

const filteredRats = computed(() => {
  if (!props.useMock) return props.rats || [];
  const f = localFilters.value || {};
  return (props.rats || []).filter((r) => matchesFilters(r, f));
});

const statisticsToUse = computed(() => {
  if (!props.useMock) return props.statistics;
  return getMockStatisticsFromRats(filteredRats.value);
});

const paginationToUse = computed(() => {
  if (!props.useMock) return props.pagination;
  const total = filteredRats.value.length;
  const lastPage = Math.max(1, Math.ceil(total / perPage));
  const safePage = Math.min(Math.max(1, currentPage.value), lastPage);

  return {
    current_page: safePage,
    last_page: lastPage,
    per_page: perPage,
    total,
  };
});

const ratsToUse = computed(() => {
  if (!props.useMock) return props.rats || [];
  const p = paginationToUse.value;
  const start = (p.current_page - 1) * p.per_page;
  return filteredRats.value.slice(start, start + p.per_page);
});

const filtersToUse = computed(() => (props.useMock ? localFilters.value : props.filters));

function handleFilterChange(newFilters) {
  if (props.useMock) {
    localFilters.value = { ...(newFilters || {}) };
    currentPage.value = 1;
    return;
  }

  router.get(route('rat.index'), newFilters, { preserveState: true, preserveScroll: true });
}

function handleFilterReset() {
  if (props.useMock) {
    localFilters.value = {};
    currentPage.value = 1;
    return;
  }

  router.get(route('rat.index'), {}, { preserveState: false, preserveScroll: false });
}

function handleView(id) {
  router.visit(route('rat.show', id));
}

function handleEdit(id) {
  router.visit(route('rat.show', id));
}

function handleAttachments(id) {
  // Abrir diretamente na aba "Anexos" (id 6) no detalhe do RAT
  router.visit(`${route('rat.show', id)}?tab=6`);
}

function handleDelete(id) {
  if (confirm('Tem certeza que deseja excluir este RAT?')) {
    // TODO: Implementar delete
    console.log('Delete RAT:', id);
  }
}

function handlePageChange(page) {
  if (props.useMock) {
    currentPage.value = Number(page) || 1;
    return;
  }

  router.get(route('rat.index'), { ...props.filters, page }, { preserveState: true, preserveScroll: true });
}
</script>

<style scoped>
.rat-index-container {
  @apply w-full min-h-screen;
  padding: 1.5rem;
  background: #0f172a;
}

@media (min-width: 640px) {
  .rat-index-container {
    padding: 1.5rem 2rem;
  }
}

@media (min-width: 1024px) {
  .rat-index-container {
    padding: 2rem 2.5rem;
  }
}

@media (min-width: 1280px) {
  .rat-index-container {
    padding: 2rem 3rem;
  }
}
</style>

