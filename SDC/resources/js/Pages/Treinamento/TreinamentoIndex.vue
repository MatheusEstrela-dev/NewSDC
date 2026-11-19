<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineOptions({ layout: AuthenticatedLayout });
import TreinamentoIndexTemplate from '@/Templates/Treinamento/TreinamentoIndexTemplate.vue';
import { usePermissions } from '@/Composables/usePermissions';
import { useToast } from '@/Composables/useToast';
import { useCursosAoVivo } from '@/Composables/treinamento/useCursosAoVivo';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import { useConfirmacao } from '@/Composables/core/useConfirmacao';

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

useCursosAoVivo(['treinamentos', 'statistics']);

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

const handleCreate = () => {
  router.visit(route('treinamentos.create'));
};

const handleView = (id) => {
  router.visit(route('treinamentos.show', id));
};

const handleEdit = (id) => {
  router.visit(route('treinamentos.edit', id));
};

const { confirmacao, pedirConfirmacao, confirmar, cancelar } = useConfirmacao();

const handleDelete = (id) => {
  pedirConfirmacao({
    title: 'Excluir capacitação',
    message: 'Tem certeza que deseja excluir esta capacitação?',
    description: 'Inscrições, conteúdo e histórico da capacitação deixam de aparecer no sistema.',
    variant: 'danger',
    confirmText: 'Excluir',
  }, () => router.delete(route('treinamentos.destroy', id), {
    preserveScroll: true,
    onSuccess: () => toast('Treinamento removido.', 'success'),
    onError: () => toast('Nao foi possivel remover o treinamento.', 'error'),
  }));
};

const handleFilter = (filters) => {
  router.visit(route('treinamentos.index'), {
    data: filters,
    preserveState: true,
    replace: true,
  });
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
      :can-validar="can('treinamento.participantes.validar')"
      @create="handleCreate"
      @view="handleView"
      @edit="handleEdit"
      @delete="handleDelete"
      @filter="handleFilter"
    />
    <ConfirmDialog :is-open="confirmacao.aberto" v-bind="confirmacao.opcoes" @confirm="confirmar" @cancel="cancelar" />
</template>
