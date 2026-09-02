<script setup>
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ReservasIndexTemplate from '@/Templates/Plantao/ReservasIndexTemplate.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  reservas: {
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
  viaturas: {
    type: Array,
    default: () => [],
  },
  agenteAtualId: {
    type: Number,
    default: null,
  },
  canCreate: {
    type: Boolean,
    default: false,
  },
  canManage: {
    type: Boolean,
    default: false,
  },
  canScan: {
    type: Boolean,
    default: false,
  },
});

const handleFilter = (filtros) => {
  router.get(route('plantao.reservas.index'), filtros, {
    preserveState: true,
    preserveScroll: true,
    only: ['reservas', 'filters'],
  });
};

const cancelDialog = ref({ open: false, loading: false, id: null });

const handleCancelar = (id) => {
  cancelDialog.value = { open: true, loading: false, id };
};

const closeCancelDialog = () => {
  if (cancelDialog.value.loading) return;
  cancelDialog.value = { open: false, loading: false, id: null };
};

const confirmCancelar = () => {
  const { id } = cancelDialog.value;
  if (!id) return;

  cancelDialog.value.loading = true;
  router.post(route('plantao.reservas.cancelar', id), {}, {
    preserveScroll: true,
    onFinish: () => {
      cancelDialog.value = { open: false, loading: false, id: null };
    },
  });
};
</script>

<template>
  <ReservasIndexTemplate
    :reservas="props.reservas.data"
    :pagination="props.reservas.pagination"
    :filters="props.filters"
    :filter-options="props.filterOptions"
    :viaturas="props.viaturas"
    :agente-atual-id="props.agenteAtualId"
    :can-create="props.canCreate"
    :can-manage="props.canManage"
    :can-scan="props.canScan"
    @filter="handleFilter"
    @cancelar="handleCancelar"
  />

  <ConfirmDialog
    :is-open="cancelDialog.open"
    variant="danger"
    title="Cancelar reserva"
    message="Tem certeza que deseja cancelar esta reserva?"
    description="A viatura volta a ficar livre nesse horario para outras pessoas."
    confirm-text="Cancelar reserva"
    cancel-text="Voltar"
    :loading="cancelDialog.loading"
    @confirm="confirmCancelar"
    @cancel="closeCancelDialog"
  />
</template>
