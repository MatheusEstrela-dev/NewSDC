<template>
  <Head title="Cisternas" />

  <div class="cisternas-index">
    <PageHeader
      title="Cisternas"
      description="Cadastro e acompanhamento das cisternas vinculadas aos municipios"
      :icon="CubeIcon"
      variant="gradient"
    >
      <template #actions>
        <Button
          v-if="canManage"
          variant="primary"
          :icon="PlusIcon"
          @click="handleCreate"
        >
          <span class="hidden sm:inline">Nova Cisterna</span>
          <span class="sm:hidden">Nova</span>
        </Button>
      </template>
    </PageHeader>

    <CisternaStatsCards :statistics="statistics" />

    <CisternaFiltersSection
      :filters="filters"
      @change="applyFilters"
    />

    <CisternaTable
      :cisternas="rows"
      :total="tableTotal"
      :can-edit="canManage"
      :can-delete="canManage"
      @show="handleShow"
      @edit="handleEdit"
      @delete="handleDelete"
    />

    <div v-if="pagination" class="mt-6">
      <Pagination
        :pagination="pagination"
        @page-change="handlePageChange"
      />
    </div>

    <ConfirmDialog
      :is-open="showDeleteConfirm"
      title="Excluir Cisterna"
      message="Tem certeza que deseja excluir esta cisterna?"
      description="Esta acao ira marcar o cadastro como excluido. Os dados podem continuar disponiveis para auditoria."
      variant="danger"
      confirm-text="Excluir"
      cancel-text="Cancelar"
      :loading="deleteLoading"
      @confirm="confirmDelete"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import CubeIcon from '@/Components/Icons/CubeIcon.vue';
import PlusIcon from '@/Components/Icons/PlusIcon.vue';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
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
const tableTotal = computed(() => props.cisternas?.meta?.total ?? rows.value.length);
const pagination = computed(() => props.cisternas?.meta ?? null);
const showDeleteConfirm = ref(false);
const deleteLoading = ref(false);
const cisternaToDelete = ref(null);

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
  cisternaToDelete.value = cisterna;
  showDeleteConfirm.value = true;
}

function applyFilters(filters) {
  router.get(route('cisternas.index'), filters, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function handlePageChange(page) {
  router.get(route('cisternas.index'), { ...props.filters, page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function confirmDelete() {
  if (!cisternaToDelete.value) return;

  deleteLoading.value = true;
  router.delete(route('cisternas.destroy', cisternaToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteConfirm.value = false;
      cisternaToDelete.value = null;
    },
    onFinish: () => {
      deleteLoading.value = false;
    },
  });
}

function cancelDelete() {
  showDeleteConfirm.value = false;
  cisternaToDelete.value = null;
}
</script>

<style scoped>
.cisternas-index {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
