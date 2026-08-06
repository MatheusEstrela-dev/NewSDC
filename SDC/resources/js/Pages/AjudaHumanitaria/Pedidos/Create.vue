<template>
  <AuthenticatedLayout>
    <Head title="Novo Pedido de Ajuda Humanitária" />

    <PedidoAhFormTemplate
      :form="form"
      :municipios="municipios"
      :cobrades="cobrades"
      :tipos-decreto="tiposDecreto"
      :municipio-fixo="municipioFixo"
      :errors="form.errors"
      :processing="form.processing"
      @campo-alterado="alterar"
      @submit="enviar"
      @cancel="cancelar"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PedidoAhFormTemplate from '@/Templates/AjudaHumanitaria/PedidoAhFormTemplate.vue';

const props = defineProps({
  municipios: { type: Array, default: () => [] },
  cobrades: { type: Array, default: () => [] },
  tiposDecreto: { type: Array, default: () => [] },
  municipioFixo: { type: Number, default: null },
  coordenador: { type: Object, default: () => ({}) },
});

// RN-05: o coordenador chega preenchido pela equipe COMPDEC do orgao.
const form = useForm({
  municipio_id: props.municipioFixo ?? '',
  cobrade_id: '',
  pop_atendida: '',
  esforcos_realizados: '',

  decreto_se_ecp_vig: false,
  tipo_decreto: '',
  numero_decreto: '',
  vigencia_decreto: '',

  nome_coordenador: props.coordenador?.nome ?? '',
  tel_coordenador: props.coordenador?.telefone ?? '',
  cel_coordenador: props.coordenador?.celular ?? '',
  email_coordenador: props.coordenador?.email ?? '',

  nome_prefeito: '',
  tel_prefeito: '',
  cel_prefeito: '',
  email_prefeito: '',
});

function alterar({ campo, valor }) {
  form[campo] = valor;
}

function enviar() {
  form.post(route('ajuda-humanitaria.pedidos.store'), { preserveScroll: true });
}

function cancelar() {
  router.get(route('ajuda-humanitaria.pedidos.index'));
}
</script>
