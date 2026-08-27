<script setup>
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ViaturasIndexTemplate from '@/Templates/Plantao/ViaturasIndexTemplate.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  viaturas: {
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
  condutores: {
    type: Array,
    default: () => [],
  },
  // Turno ATIVO corrente; null quando nao ha turno aberto.
  plantaoAtivoId: {
    type: Number,
    default: null,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

// Reload parcial: so viaturas/filters mudam, statistics nao e recalculada de novo
// a cada filtro (prop closure do Inertia roda em toda visita completa).
const handleFilter = (filtros) => {
  router.get(route('plantao.viaturas.index'), filtros, {
    preserveState: true,
    preserveScroll: true,
    only: ['viaturas', 'filters'],
  });
};

const deleteDialog = ref({ open: false, loading: false, id: null });

const handleDelete = (id) => {
  deleteDialog.value = { open: true, loading: false, id };
};

const closeDeleteDialog = () => {
  if (deleteDialog.value.loading) return;
  deleteDialog.value = { open: false, loading: false, id: null };
};

const confirmDelete = () => {
  const { id } = deleteDialog.value;
  if (!id) return;

  deleteDialog.value.loading = true;
  router.delete(route('plantao.viaturas.destroy', id), {
    preserveScroll: true,
    onSuccess: () => closeDeleteDialog(),
    onFinish: () => { deleteDialog.value.loading = false; },
  });
};
</script>

<template>
  <ViaturasIndexTemplate
    :viaturas="viaturas.data"
    :statistics="statistics"
    :pagination="viaturas.pagination"
    :filters="filters"
    :filter-options="filterOptions"
    :condutores="condutores"
    :plantao-ativo-id="plantaoAtivoId"
    :can-create="canCreate"
    :can-edit="canEdit"
    :can-delete="canDelete"
    @filter="handleFilter"
    @delete="handleDelete"
  />

  <ConfirmDialog
    :is-open="deleteDialog.open"
    variant="danger"
    title="Excluir viatura"
    message="Tem certeza que deseja excluir esta viatura?"
    description="A viatura sera removida da frota (exclusao reversivel)."
    confirm-text="Excluir"
    cancel-text="Cancelar"
    :loading="deleteDialog.loading"
    @confirm="confirmDelete"
    @cancel="closeDeleteDialog"
  />
</template>
