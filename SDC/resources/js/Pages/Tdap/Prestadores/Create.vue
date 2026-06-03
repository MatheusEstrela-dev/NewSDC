<template>
  <Head title="TDAP — Novo Prestador" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Novo Prestador"
      description="Cadastrar empresa contratada para transporte de água"
      :icon="BuildingIcon"
    />
    <PrestadorForm
      :form="form"
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
import PrestadorForm from '@/Components/Organisms/Tdap/PrestadorForm.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const form = useForm({
  cnpj: '',
  nome: '',
  representante: '',
  email: '',
  tel1: '',
  tel2: '',
  endereco: '',
  bairro: '',
  cidade: '',
  uf: '',
  cep: '',
  ativo: true,
  observacoes: '',
});

function submit() {
  form.post(route('tdap.prestadores.store'));
}

function cancelar() {
  router.visit(route('tdap.prestadores.index'));
}
</script>
