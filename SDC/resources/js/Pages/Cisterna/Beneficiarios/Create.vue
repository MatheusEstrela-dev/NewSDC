<template>
  <AuthenticatedLayout>
    <Head title="Cisternas — Novo beneficiario" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
      <header>
        <Link
          :href="route('cisternas.beneficiarios.index')"
          class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
        >
          &larr; Beneficiarios
        </Link>
        <h1 class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">Novo beneficiario</h1>
      </header>

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
