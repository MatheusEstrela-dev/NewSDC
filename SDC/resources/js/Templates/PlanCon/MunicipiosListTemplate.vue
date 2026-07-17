<template>
  <div class="space-y-6">
    <PageHeader :title="title" :description="description" :icon="icon" variant="gradient">
      <template #actions>
        <Button v-if="canExport" variant="success" size="md" :icon="ArrowDownTrayIcon" @click="emit('export')">Exportar</Button>
      </template>
    </PageHeader>

    <PlanConMunicipiosFilters :filters="filters" @change="applyFilters" />

    <PlanConMunicipiosTable
      :municipios="municipios"
      :total="resolvedTotal"
      :show-situacao="showSituacao"
      :show-data-atualizacao="showDataAtualizacao"
      @view="emit('view', $event)"
    />

    <Pagination v-if="pagination && pagination.last_page > 1" :pagination="pagination" @page-change="changePage" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ArrowDownTrayIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import PlanConMunicipiosFilters from '@/Components/Organisms/PlanCon/PlanConMunicipiosFilters.vue';
import PlanConMunicipiosTable from '@/Components/Organisms/PlanCon/PlanConMunicipiosTable.vue';

const props = defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: [Object, Function], required: true },
  municipios: { type: Array, default: () => [] },
  totalMunicipios: { type: Number, default: 0 },
  showSituacao: { type: Boolean, default: false },
  showDataAtualizacao: { type: Boolean, default: false },
  canExport: { type: Boolean, default: false },
  pagination: { type: Object, default: null },
  filters: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['export', 'view', 'filter']);
const resolvedTotal = computed(() => props.pagination?.total ?? props.totalMunicipios ?? props.municipios.length);
const applyFilters = filters => emit('filter', filters);
const changePage = page => emit('filter', { ...props.filters, page });
</script>
