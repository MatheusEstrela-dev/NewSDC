<template>
  <CisternaFormTemplate
    title="Nova Cisterna"
    subtitle="Cadastrar nova cisterna no sistema"
    :form-data="formData"
    :errors="errors"
    :loading="loading"
    submit-label="Cadastrar"
    @submit="handleSubmit"
  />
</template>

<script setup>
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CisternaFormTemplate from '@/Templates/Cisterna/CisternaFormTemplate.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  errors: { type: Object, default: () => ({}) },
});

const loading = ref(false);

const formData = reactive({
  municipio_id: null,
  codigo: '',
  nome: '',
  tipo: '',
  status: 'pendente',
  endereco: '',
  latitude: '',
  longitude: '',
  capacidade_litros: null,
  data_instalacao: '',
  responsavel_nome: '',
  responsavel_telefone: '',
  observacoes: '',
});

function handleSubmit(payload) {
  loading.value = true;
  router.post(route('cisternas.store'), payload, {
    onFinish: () => { loading.value = false; },
  });
}
</script>
