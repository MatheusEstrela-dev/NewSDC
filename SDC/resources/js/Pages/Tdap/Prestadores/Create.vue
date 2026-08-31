<template>
  <Head title="TDAP — Novo Prestador" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Novo Prestador"
      description="Cadastrar empresa contratada para transporte de água"
      :icon="BuildingIcon"
    />
    <PrestadorForm
      :form="form"
      :ufs="ufs"
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

defineProps({
  ufs: { type: Array, default: () => [] },
});

// Documentos vivem em digitos puros no form; a mascara e do PrestadorForm.
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
  // preserveScroll mantem a pagina no campo com erro em vez de voltar ao topo.
  form.post(route('tdap.prestadores.store'), { preserveScroll: true });
}

function cancelar() {
  router.visit(route('tdap.prestadores.index'));
}
</script>
