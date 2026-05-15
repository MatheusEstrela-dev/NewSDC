<template>
  <Head title="TDAP — Novo Cronograma" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Novo Cronograma"
      description="Ordem operacional de fornecimento de água potável"
      :icon="TruckIcon"
    />
    <CronogramaForm
      :form="form"
      :atas="atas"
      :lotes="lotes"
      submit-label="Cadastrar"
      @submit="submit"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import CronogramaForm from '@/Components/Organisms/Tdap/CronogramaForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  atas:        { type: Array, default: () => [] },
  lotes:       { type: Array, default: () => [] },
  municipios:  { type: Array, default: () => [] },
  prestadores: { type: Array, default: () => [] },
});

const form = useForm({
  numero: '',
  empenho: '',
  ata_id: null,
  lote_id: null,
  municipio_id: null,
  prestador_id: null,
  cnpj: null,
  consumo_diario: '',
  dias: '',
  fator: 1,
  dt_inicio: '',
  dt_final: '',
  justificativa: '',
  nota_empenho: '',
  ponto_captacao_id: null,
  observacao: '',
});

function submit() { form.post(route('tdap.cronogramas.store')); }
function cancelar() { router.visit(route('tdap.cronogramas.index')); }
</script>
