<script setup>
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import TreinamentoIndexTemplate from '@/Templates/Treinamento/TreinamentoIndexTemplate.vue';
import TreinamentoFormModal from '@/Components/Organisms/Treinamento/TreinamentoFormModal.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';

const { can } = usePermissions();
const { show: toast } = useToast();

const props = defineProps({
  treinamentos: {
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

const pagination = computed(() => {
  const m = props.treinamentos?.meta;
  if (!m) return null;
  return {
    current_page: m.current_page ?? 1,
    last_page: m.last_page ?? 1,
    per_page: m.per_page ?? 15,
    total: m.total ?? 0,
    from: m.from ?? null,
    to: m.to ?? null,
  };
});

const showFormModal = ref(false);
const editingTreinamento = ref(null);

const handleCreate = () => {
  editingTreinamento.value = null;
  showFormModal.value = true;
};

const handleView = (id) => {
  router.visit(route('treinamentos.show', id));
};

const handleEdit = (id) => {
  const treinamento = (props.treinamentos.data || []).find((t) => t.id === id);
  editingTreinamento.value = treinamento || { id };
  showFormModal.value = true;
};

const handleDelete = (id) => {
  if (!confirm('Tem certeza que deseja excluir este treinamento?')) return;

  router.delete(route('treinamentos.destroy', id), {
    preserveScroll: true,
    onSuccess: () => toast('Treinamento removido.', 'success'),
    onError: () => toast('Nao foi possivel remover o treinamento.', 'error'),
  });
};

const handleFilter = (filters) => {
  router.visit(route('treinamentos.index'), {
    data: filters,
    preserveState: true,
    replace: true,
  });
};

const handleSaved = () => {
  router.reload({ only: ['treinamentos', 'statistics'] });
};
</script>

<template>

    <TreinamentoIndexTemplate
      :treinamentos="treinamentos.data"
      :statistics="statistics"
      :pagination="pagination"
      :filters="filters"
      :filter-options="filterOptions"
      :can-create="can('treinamento.cursos.create')"
      :can-edit="can('treinamento.cursos.edit')"
      :can-delete="can('treinamento.cursos.delete')"
      :can-export="can('treinamento.cursos.export')"
      @create="handleCreate"
      @view="handleView"
      @edit="handleEdit"
      @delete="handleDelete"
      @filter="handleFilter"
    />

    <TreinamentoFormModal
      :show="showFormModal"
      :treinamento="editingTreinamento"
      :filter-options="filterOptions"
      @close="showFormModal = false"
      @saved="handleSaved"
    />

</template>
