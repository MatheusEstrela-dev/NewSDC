<template>
  <AuthenticatedLayout>
    <Head title="TDAP - Recebimentos" />
    <TdapRecebimentosTemplate
      :recebimentos="recebimentos"
      :pagination="pagination"
      :filters="filters"
      :statistics="statistics"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapRecebimentosTemplate from '@/Templates/Tdap/TdapRecebimentosTemplate.vue';

const props = defineProps({
  recebimentos: {
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
