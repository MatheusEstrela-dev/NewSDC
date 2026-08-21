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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import CaminhaoForm from '@/Components/Organisms/Tdap/CaminhaoForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestadores: { type: Array, default: () => [] },
  // Pre-selecao vinda da ficha do prestador (?prestador_id=). O backend so
  // devolve o id se ele estiver na lista de prestadores ativos.
  prestadorId: { type: Number, default: null },
});

const form = useForm({
  prestador_id: props.prestadorId,
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
