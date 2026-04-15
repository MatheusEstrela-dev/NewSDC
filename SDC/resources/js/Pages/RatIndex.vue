<template>
    <div>
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
          :can-create="true"
          :can-edit="true"
          :can-delete="true"
          :can-export="true"
          :can-finalize="true"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    getDefaultYears,
    getMockRats,
    getMockStatisticsFromRats,
    mockCobradeTypes,
    mockMunicipalities,
} from '@/mocks/rat';
import RatIndexTemplate from '@/Templates/Rat/RatIndexTemplate.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

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
    type: [Array, Object],
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
  if (!props.rats) return true;
  if (Array.isArray(props.rats)) return props.rats.length === 0;
  return !props.rats.data || props.rats.data.length === 0;
});

const effectiveRats = computed(() => {
  if (useMock.value) return getMockRats();
  return Array.isArray(props.rats) ? props.rats : (props.rats.data || []);
});
const effectiveStatistics = computed(() => {
  if (useMock.value) {
    return getMockStatisticsFromRats(effectiveRats.value);
  }
  return props.statistics;
});
const effectiveFilters = computed(() => (useMock.value ? {} : props.filters));
const effectivePagination = computed(() => {
  if (useMock.value) return null;
  if (props.pagination) return props.pagination;
  if (props.rats && !Array.isArray(props.rats)) {
    return {
      current_page: props.rats.current_page,
      last_page: props.rats.last_page,
      per_page: props.rats.per_page,
      total: props.rats.total,
      links: props.rats.links,
    };
  }
  return null;
});
const effectiveMunicipalities = computed(() => (props.municipalities && props.municipalities.length > 0 ? props.municipalities : mockMunicipalities));
const effectiveCobradeTypes = computed(() => (props.cobradeTypes && props.cobradeTypes.length > 0 ? props.cobradeTypes : mockCobradeTypes));
const effectiveYears = computed(() => (props.years && props.years.length > 0 ? props.years : getDefaultYears()));
</script>
