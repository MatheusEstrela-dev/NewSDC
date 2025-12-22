<template>
  <AuthenticatedLayout>
    <Head title="Gestão de RAT" />
    <RatIndexTemplate
      :statistics="effectiveStatistics"
      :rats="effectiveRats"
      :filters="effectiveFilters"
      :pagination="effectivePagination"
      :municipalities="effectiveMunicipalities"
      :cobrade-types="effectiveCobradeTypes"
      :years="effectiveYears"
      :loading="false"
      :use-mock="useMock"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RatIndexTemplate from '@/Templates/Rat/RatIndexTemplate.vue';
import {
  getMockRats,
  getMockStatisticsFromRats,
  mockMunicipalities,
  mockCobradeTypes,
  getDefaultYears,
} from '@/mocks/rat';

const props = defineProps({
  statistics: {
    type: Object,
    default: () => ({
      total: 0,
      hoje: 0,
      esteMes: 0,
      esteAno: 0,
    }),
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
});

// Frontend-only: por enquanto, usar mocks. Depois é só trocar para false ou usar env flag.
const useMock = true;

const effectiveRats = computed(() => (useMock ? getMockRats() : props.rats));
const effectiveStatistics = computed(() =>
  useMock ? getMockStatisticsFromRats(effectiveRats.value) : props.statistics
);
const effectiveFilters = computed(() => (useMock ? {} : props.filters));
const effectivePagination = computed(() => (useMock ? null : props.pagination));
const effectiveMunicipalities = computed(() => (useMock ? mockMunicipalities : props.municipalities));
const effectiveCobradeTypes = computed(() => (useMock ? mockCobradeTypes : props.cobradeTypes));
const effectiveYears = computed(() => (useMock ? getDefaultYears() : props.years));
</script>
