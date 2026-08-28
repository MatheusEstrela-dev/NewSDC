<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import { useToast } from '@/Composables/useToast';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
  plantao: {
    type: Object,
    required: true,
  },
});

const { show: toast } = useToast();

// Recorte editavel (decisao do plano): so o que pertence ao turno, nao ao
// historico ja declarado. plantonista, data/periodo, status e snapshots nao
// entram aqui - mudam so pela maquina de estados da passagem de servico.
const form = useForm({
  localizacao: props.plantao.localizacao ?? '',
  observacoes: props.plantao.observacoes ?? '',
  ocorrencias_destaque: props.plantao.ocorrencias_destaque ?? '',
});

const salvar = () => {
  form.put(route('plantao.update', props.plantao.id), {
    onError: () => toast('Verifique os campos do formulario.', 'error'),
  });
};

const cancelar = () => {
  router.visit(route('plantao.show', props.plantao.id));
};
</script>

<template>
  <div class="plantao-edit-container">
    <PageHeader
      title="Editar turno"
      :description="`${plantao.data} (${plantao.periodo_label}) - ${plantao.plantonista_nome}`"
      :icon="ClockIcon"
      :icon-image="moduleIcon('plantao')"
      variant="gradient"
    />

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/40">
      <div v-if="form.processing" class="mb-4 h-1 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
        <div class="h-full w-1/2 animate-pulse rounded-full bg-blue-600" />
      </div>

      <div class="space-y-4">
        <FormField
          v-model="form.localizacao"
          label="Localização"
          placeholder="Ex: Prédio Alterosas"
          :maxlength="255"
          :error="form.errors.localizacao"
        />

        <FormTextarea
          v-model="form.observacoes"
          label="Observações"
          :rows="4"
          :error="form.errors.observacoes"
        />

        <FormTextarea
          v-model="form.ocorrencias_destaque"
          label="Ocorrências de destaque"
          :rows="4"
          :error="form.errors.ocorrencias_destaque"
        />
      </div>

      <footer class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-700/50">
        <FormActions
          submit-label="Salvar"
          submit-variant="success"
          :loading="form.processing"
          @cancel="cancelar"
          @submit="salvar"
        />
      </footer>
    </div>
  </div>
</template>

<style scoped>
.plantao-edit-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}
</style>
