<template>
  <AuthenticatedLayout>
    <Head title="TDAP - Movimentações" />
    <TdapMovimentacoesTemplate
      :movimentacoes="movimentacoes"
      :pagination="pagination"
      :filters="filters"
      :statistics="statistics"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapMovimentacoesTemplate from '@/Templates/Tdap/TdapMovimentacoesTemplate.vue';

const props = defineProps({
  movimentacoes: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      per_page: 15,
      total: 0,
      last_page: 1,
    }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  statistics: {
    type: Object,
    default: () => ({}),
  },
});
</script>
