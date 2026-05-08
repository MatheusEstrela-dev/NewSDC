<template>
  <Head title="Cisternas" />

  <div class="cisternas-index">
    <PageHeader
      title="Cisternas"
      kicker="Modulo de gestao"
      subtitle="Cadastro e acompanhamento das cisternas vinculadas aos municipios."
    >
      <template #actions>
        <Button
          v-if="canManage"
          variant="primary"
          :icon="PlusIcon"
          @click="handleCreate"
        >
          Nova Cisterna
        </Button>
      </template>
    </PageHeader>

    <CisternaStatsCards :statistics="statistics" class="mb-6" />

    <CisternaFiltersSection
      :filters="filters"
      class="mb-6"
      @change="applyFilters"
    />

    <CisternaTable
      :cisternas="rows"
      :can-edit="canManage"
      :can-delete="canManage"
      @show="handleShow"
      @edit="handleEdit"
      @delete="handleDelete"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import CisternaStatsCards from '@/Components/Organisms/Cisterna/CisternaStatsCards.vue';
import CisternaFiltersSection from '@/Components/Organisms/Cisterna/CisternaFiltersSection.vue';
import CisternaTable from '@/Components/Organisms/Cisterna/CisternaTable.vue';

const props = defineProps({
  cisternas: { type: Object, default: () => ({ data: [] }) },
  statistics: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  canManage: { type: Boolean, default: false },
});

const rows = computed(() => props.cisternas?.data ?? props.cisternas ?? []);

function handleCreate() {
  router.visit(route('cisternas.create'));
}

function handleShow(cisterna) {
  router.visit(route('cisternas.show', cisterna.id));
}

function handleEdit(cisterna) {
  router.visit(route('cisternas.edit', cisterna.id));
}

function handleDelete(cisterna) {
  if (!confirm(`Excluir cisterna "${cisterna.nome}"?`)) return;
  router.delete(route('cisternas.destroy', cisterna.id), { preserveScroll: true });
}

function applyFilters(filters) {
  router.get(route('cisternas.index'), filters, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}
</script>

<style scoped>
.cisternas-index {
  max-width: 1400px;
  margin: 0 auto;
}
</style>
