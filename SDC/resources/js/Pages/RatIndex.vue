<template>
  <AuthenticatedLayout>
    <Head title="Gestao de RAT" />
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
      :can-create="can('rat.protocolos.create')"
      :can-edit="can('rat.protocolos.edit')"
      :can-delete="can('rat.protocolos.delete')"
      :can-export="can('rat.protocolos.export')"
      :can-finalize="can('rat.protocolos.finalize')"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RatIndexTemplate from '@/Templates/Rat/RatIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();
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

// Usar dados reais do backend quando disponíveis, caso contrário usar mocks
const useMock = computed(() => {
  // Se não houver dados do backend ou se a lista estiver vazia, usar mocks
  return !props.rats || props.rats.length === 0;
});

const effectiveRats = computed(() => useMock.value ? getMockRats() : props.rats);
const effectiveStatistics = computed(() => {
  if (useMock.value) {
    return getMockStatisticsFromRats(effectiveRats.value);
  }
  return props.statistics;
});
const effectiveFilters = computed(() => (useMock.value ? {} : props.filters));
const effectivePagination = computed(() => (useMock.value ? null : props.pagination));
const effectiveMunicipalities = computed(() => (useMock.value ? mockMunicipalities : props.municipalities));
const effectiveCobradeTypes = computed(() => (useMock.value ? mockCobradeTypes : props.cobradeTypes));
const effectiveYears = computed(() => (useMock.value ? getDefaultYears() : props.years));
</script>
