<template>
  <AuthenticatedLayout>
    <Head title="TDAP - Produtos" />
    <TdapProductsTemplate
      :products="products"
      :pagination="pagination"
      :filters="filters"
      :statistics="statistics"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapProductsTemplate from '@/Templates/Tdap/TdapProductsTemplate.vue';

const props = defineProps({
  products: {
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
