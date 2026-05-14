<template>
  <Head title="TDAP — Nova Ata" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Nova Ata de Registro de Preços"
      description="Cadastrar contrato-pai para fornecimento de água potável"
      :icon="CalendarIcon"
    />
    <AtaForm :form="form" submit-label="Cadastrar" @submit="submit" @cancel="cancelar" />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import AtaForm from '@/Components/Organisms/Tdap/AtaForm.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const form = useForm({
  numero: '',
  dt_inicio: '',
  dt_final: '',
  historico: '',
  ativo: true,
  observacoes: '',
});

function submit() { form.post(route('tdap.atas.store')); }
function cancelar() { router.visit(route('tdap.atas.index')); }
</script>
