<template>
  <Head :title="`TDAP — ${p.nome}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar: ${p.nome}`"
      :description="p.cnpj_formatado"
      :icon="BuildingIcon"
    />
    <PrestadorForm
      :form="form"
      :ufs="ufs"
      submit-label="Salvar alterações"
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

const props = defineProps({
  prestador: { type: Object, required: true },
  ufs: { type: Array, default: () => [] },
});

const p = props.prestador.data ?? props.prestador;

// Os campos de documento recebem o valor CRU (digitos), nao o `*_formatado`:
// o form e a mascara compartilham o contrato de digitos puros, e semear com o
// valor mascarado obrigava o backend a limpar de novo o que ele mesmo gravou.
const form = useForm({
  cnpj: p.cnpj || '',
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
  form.put(route('tdap.prestadores.update', p.id), { preserveScroll: true });
}

function cancelar() {
  router.visit(route('tdap.prestadores.show', p.id));
}
</script>
