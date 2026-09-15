<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Novo beneficiario" />

    <div class="w-full space-y-6 pb-8">
      <PageHeader
        title="Novo beneficiario"
        description="Cadastro para o programa de cisternas"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
      </PageHeader>

      <BeneficiarioForm
        modo="criar"
        :form="form"
        :opcoes="opcoes"
        :comunidades="comunidades"
        :carregando-comunidades="carregandoComunidades"
        :processando="form.processing"
        @municipio="carregarComunidades"
        @arquivo="anexar"
        @submit="salvar"
        @cancel="cancelar"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import BeneficiarioForm from '@/Components/Organisms/Cisterna/BeneficiarioForm.vue';
import { useBeneficiarioForm } from '@/Composables/cisterna/useBeneficiarioForm';

defineProps({
  opcoes: { type: Object, default: () => ({}) },
});

const {
  form,
  comunidades,
  carregandoComunidades,
  carregarComunidades,
  anexar,
  salvar,
  cancelar,
} = useBeneficiarioForm();
</script>
