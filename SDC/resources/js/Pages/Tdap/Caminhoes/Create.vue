<template>
  <Head title="TDAP — Novo Caminhão" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Novo Caminhão-Tanque"
      description="Cadastrar veículo de um prestador"
      :icon="TruckIcon"
    />
    <CaminhaoForm
      :form="form"
      :prestadores="prestadores"
      submit-label="Cadastrar"
      @submit="submit"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import CaminhaoForm from '@/Components/Organisms/Tdap/CaminhaoForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

defineProps({
  prestadores: { type: Array, default: () => [] },
});

const form = useForm({
  prestador_id: null,
  placa: '',
  marca: '',
  modelo: '',
  cor: '',
  ano: '',
  capacidade_m3: '',
  ativo: true,
  observacoes: '',
});

function submit() {
  form.post(route('tdap.caminhoes.store'));
}

function cancelar() {
  router.visit(route('tdap.caminhoes.index'));
}
</script>
