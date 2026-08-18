<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Novo beneficiario" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Novo beneficiario"
        description="Cadastro para o programa de cisternas"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <Link :href="route('cisternas.beneficiarios.index')" :class="VOLTAR">Voltar</Link>
        </template>
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
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import BeneficiarioForm from '@/Components/Organisms/Cisterna/BeneficiarioForm.vue';
import { useBeneficiarioForm } from '@/Composables/cisterna/useBeneficiarioForm';

defineProps({
  opcoes: { type: Object, default: () => ({}) },
});

const VOLTAR = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

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
