<template>
  <AuthenticatedLayout>
    <Head :title="titulo" />

    <BeneficiarioShowTemplate :beneficiario="beneficiario" />
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import BeneficiarioShowTemplate from '@/Templates/AjudaHumanitaria/BeneficiarioShowTemplate.vue';

const props = defineProps({
  beneficiario: { type: Object, required: true },
});

const titulo = computed(() => props.beneficiario.nome_responsavel || 'Beneficiário');
</script>
