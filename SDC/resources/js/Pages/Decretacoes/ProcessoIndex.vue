<template>
    <div>
        <Head title="Decretações" />
        <ProcessoIndexTemplate
          :processos="processos.data || []"
          :statistics="statistics"
          :filters="filters"
          :filter-options="filterOptions"
          :pagination="processos"
          :loading="loading"
          :can-create="can('decretacoes.processos.create')"
          :can-edit="can('decretacoes.processos.edit')"
          :can-delete="can('decretacoes.processos.delete')"
          :can-export="can('decretacoes.processos.export')"
          @filter-change="handleFilterChange"
          @clear-filters="handleClearFilters"
          @page-change="handlePageChange"
          @create="handleCreate"
          @delete="handleDelete"
        />
    </div>
</template>

<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProcessoIndexTemplate from '@/Templates/Decretacoes/ProcessoIndexTemplate.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  processos: {
    type: Object,
    required: true,
  },
  statistics: {
    type: Object,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const loading = ref(false);

const handleFilterChange = (filters) => {
  loading.value = true;
  router.get(route('decretacoes.index'), filters, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handleClearFilters = () => {
  loading.value = true;
  router.get(route('decretacoes.index'), {}, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handlePageChange = (page) => {
  loading.value = true;
  router.get(route('decretacoes.index'), {
    ...props.filters,
    page,
  }, {
    preserveState: true,
    preserveScroll: true,
    onFinish: () => {
      loading.value = false;
    },
  });
};

const handleCreate = () => {
  router.visit(route('decretacoes.create'));
};

const handleDelete = (id) => {
  router.delete(route('decretacoes.destroy', id), {
    preserveState: false,
    preserveScroll: true,
  });
};
</script>
