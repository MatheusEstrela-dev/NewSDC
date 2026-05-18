<template>
  <Head :title="`TDAP — ${prestador.data.nome}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar: ${prestador.data.nome}`"
      :description="prestador.data.cnpj_formatado"
      :icon="BuildingIcon"
    />
    <PrestadorForm
      :form="form"
      submit-label="Salvar alterações"
      @submit="submit"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import PrestadorForm from '@/Components/Organisms/Tdap/PrestadorForm.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  prestador: { type: Object, required: true },
});

const p = props.prestador.data;

const form = useForm({
  cnpj: p.cnpj_formatado || p.cnpj || '',
  nome: p.nome || '',
  representante: p.representante || '',
  email: p.email || '',
  tel1: p.tel1 || '',
  tel2: p.tel2 || '',
  endereco: p.endereco || '',
  bairro: p.bairro || '',
  cidade: p.cidade || '',
  uf: p.uf || '',
  cep: p.cep || '',
  ativo: p.ativo ?? true,
  observacoes: p.observacoes || '',
});

function submit() {
  form.put(route('tdap.prestadores.update', p.id));
}

function cancelar() {
  router.visit(route('tdap.prestadores.show', p.id));
}
</script>
