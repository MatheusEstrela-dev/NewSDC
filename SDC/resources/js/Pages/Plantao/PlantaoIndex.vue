<script setup>
import { usePermissions } from '@/Composables/usePermissions';
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlantaoIndexTemplate from '@/Templates/Plantao/PlantaoIndexTemplate.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const { can } = usePermissions();

const props = defineProps({
  plantoes: {
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
  // Listas: pode haver mais de um turno ATIVO (periodos diferentes) e mais de
  // um PENDENTE_ACEITE, porque abrir turno nao bloqueia o pendente (spec 4.2).
  turnosAtivos: {
    type: Array,
    default: () => [],
  },
  turnosPendentes: {
    type: Array,
    default: () => [],
  },
  canEncerrar: {
    type: Boolean,
    default: false,
  },
  canAceitar: {
    type: Boolean,
    default: false,
  },
  canRelatorio: {
    type: Boolean,
    default: false,
  },
  canEscala: {
    type: Boolean,
    default: false,
  },
});

const handleView = (id) => {
  router.visit(route('plantao.show', id));
};

const handleEdit = (id) => {
  router.visit(route('plantao.edit', id));
};

const handleFilter = (filters) => {
  // Reload parcial: turnosAtivos/turnosPendentes fazem query propria e nao
  // dependem do filtro da listagem. Sem o `only`, toda troca de filtro
  // recalcularia os dois a cada visita completa do Inertia.
  router.visit(route('plantao.index'), {
    data: filters,
    only: ['plantoes', 'filters'],
    preserveState: true,
    replace: true,
  });
};

const handleAbrirPlantao = (dados) => {
  // O plantonista responsavel e o usuario autenticado, resolvido no
  // PassagemAbrirController: nao viaja no payload.
  router.post(route('plantao.passagem.abrir'), {
    data: dados.data,
    periodo: dados.periodo,
  }, {
    preserveScroll: true,
  });
};

/**
 * Exclusao do turno. Ate aqui o icone de lixeira emitia um evento que NENHUM
 * listener escutava -- o clique sumia no caminho, sem erro e sem efeito.
 *
 * Confirmacao explicita no padrao do projeto (mesmo dialogo do PAE e da frota):
 * a exclusao e suave, e o texto diz isso, porque o turno carrega passagem de
 * servico e aceite formal de duas partes.
 */
const dialogExcluir = ref({ open: false, loading: false, id: null });

const pedirExclusao = (id) => {
  dialogExcluir.value = { open: true, loading: false, id };
};

const cancelarExclusao = () => {
  if (dialogExcluir.value.loading) return;
  dialogExcluir.value = { open: false, loading: false, id: null };
};

const confirmarExclusao = () => {
  const { id } = dialogExcluir.value;
  if (!id) return;

  dialogExcluir.value.loading = true;
  router.delete(route('plantao.destroy', id), {
    preserveScroll: true,
    onFinish: () => {
      dialogExcluir.value = { open: false, loading: false, id: null };
    },
  });
};
</script>

<template>
  <PlantaoIndexTemplate
    :plantoes="plantoes.data"
    :statistics="statistics"
    :pagination="plantoes.pagination"
    :filters="filters"
    :filter-options="filterOptions"
    :can-create="can('plantao.turnos.create')"
    :can-edit="can('plantao.turnos.edit')"
    :can-delete="can('plantao.turnos.delete')"
    :can-export="can('plantao.turnos.export')"
    :turnos-ativos="turnosAtivos"
    :turnos-pendentes="turnosPendentes"
    :can-encerrar="canEncerrar"
    :can-aceitar="canAceitar"
    :can-relatorio="canRelatorio"
    :can-escala="canEscala"
    @view="handleView"
    @edit="handleEdit"
    @delete="pedirExclusao"
    @filter="handleFilter"
    @abrir-plantao="handleAbrirPlantao"
  />

  <ConfirmDialog
    :is-open="dialogExcluir.open"
    variant="danger"
    title="Excluir Turno"
    message="Tem certeza que deseja excluir este turno?"
    description="Esta acao marcara o turno como excluido. Os dados serao preservados para auditoria, e a vaga do periodo fica livre para reabrir."
    confirm-text="Excluir"
    cancel-text="Cancelar"
    :loading="dialogExcluir.loading"
    @confirm="confirmarExclusao"
    @cancel="cancelarExclusao"
  />
</template>
