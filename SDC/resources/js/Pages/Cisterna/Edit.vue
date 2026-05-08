<template>
  <CisternaFormTemplate
    :title="`Editar: ${cisterna.data?.nome ?? cisterna.nome}`"
    subtitle="Atualizar dados da cisterna"
    :form-data="formData"
    :errors="errors"
    :loading="loading"
    submit-label="Salvar"
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
  cisterna: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
});

const loading = ref(false);
const data = props.cisterna?.data ?? props.cisterna ?? {};

const formData = reactive({
  municipio_id: data.municipio_id ?? null,
  codigo: data.codigo ?? '',
  nome: data.nome ?? '',
  tipo: data.tipo ?? '',
  status: data.status ?? 'pendente',
  endereco: data.endereco ?? '',
  latitude: data.latitude ?? '',
  longitude: data.longitude ?? '',
  capacidade_litros: data.capacidade_litros ?? null,
  data_instalacao: data.data_instalacao ?? '',
  responsavel_nome: data.responsavel_nome ?? '',
  responsavel_telefone: data.responsavel_telefone ?? '',
  observacoes: data.observacoes ?? '',
});

function handleSubmit(payload) {
  loading.value = true;
  router.put(route('cisternas.update', data.id), payload, {
    onFinish: () => { loading.value = false; },
  });
}
</script>
