<script setup>
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import TreinamentoIndexTemplate from '@/Templates/Treinamento/TreinamentoIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';

const { can } = usePermissions();

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

const handleCreate = () => {
  // TODO: Implementar modal ou página de criação
  console.log('Criar novo treinamento');
};

const handleView = (id) => {
  router.visit(route('treinamentos.show', id));
};

const handleEdit = (id) => {
  // TODO: Implementar edição
  console.log('Editar treinamento', id);
};

const handleDelete = (id) => {
  if (confirm('Tem certeza que deseja excluir este treinamento?')) {
    // TODO: Implementar exclusão
    console.log('Deletar treinamento', id);
  }
};

const handleFilter = (filters) => {
  router.visit(route('treinamentos.index'), {
    data: filters,
    preserveState: true,
  });
};
</script>

<template>

    <TreinamentoIndexTemplate
      :treinamentos="treinamentos.data"
      :statistics="statistics"
      :pagination="treinamentos.pagination"
      :filters="filters"
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

</template>
