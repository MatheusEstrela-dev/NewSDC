<script setup>
import ConfirmDialog from '@/Components/Admin/ConfirmDialog.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PlantonistasIndexTemplate from '@/Templates/Plantao/PlantonistasIndexTemplate.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  plantonistas: { type: Array, default: () => [] },
  statistics: { type: Object, default: () => ({}) },
  filtros: { type: Object, default: () => ({ busca: '' }) },
  candidatos: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
});

const dialogRemover = ref({ open: false, loading: false, alvo: null });

// Reload parcial: a lista de plantonistas nao muda ao buscar candidatos.
const buscar = (termo) => {
  router.get(
    route('plantao.plantonistas.index'),
    { busca: termo },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['candidatos', 'filtros'],
    },
  );
};

const adicionar = (dados) => {
  router.post(route('plantao.plantonistas.store'), dados, {
    preserveScroll: true,
  });
};

const atualizar = (plantonista) => {
  router.put(
    route('plantao.plantonistas.update', plantonista.id),
    {
      posto: plantonista.posto ?? null,
      ativo: plantonista.ativo,
      observacao: plantonista.observacao ?? null,
    },
    { preserveScroll: true },
  );
};

const pedirRemocao = (plantonista) => {
  dialogRemover.value = { open: true, loading: false, alvo: plantonista };
};

const confirmarRemocao = () => {
  const alvo = dialogRemover.value.alvo;
  if (!alvo) return;

  dialogRemover.value.loading = true;
  router.delete(route('plantao.plantonistas.destroy', alvo.id), {
    preserveScroll: true,
    onFinish: () => {
      dialogRemover.value = { open: false, loading: false, alvo: null };
    },
  });
};
</script>

<template>
  <PlantonistasIndexTemplate
    :plantonistas="plantonistas"
    :statistics="statistics"
    :filtros="filtros"
    :candidatos="candidatos"
    :can="can"
    :errors="errors"
    @buscar="buscar"
    @adicionar="adicionar"
    @atualizar="atualizar"
    @remover="pedirRemocao"
  />

  <ConfirmDialog
    :is-open="dialogRemover.open"
    variant="danger"
    title="Remover plantonista"
    :message="`Remover ${dialogRemover.alvo?.nome_com_posto ?? ''} da lista de escalaveis?`"
    description="As vagas ja escaladas continuam no historico: elas guardam o nome no momento da escala. Ele apenas deixa de aparecer para novas escalas."
    confirm-text="Remover"
    cancel-text="Cancelar"
    :loading="dialogRemover.loading"
    @confirm="confirmarRemocao"
    @cancel="dialogRemover = { open: false, loading: false, alvo: null }"
  />
</template>
