<template>
  <Head title="TDAP — Nova Vistoria" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Nova Vistoria"
      description="Inspeção técnica do caminhão-tanque (27 + 7 itens)"
      :icon="TruckIcon"
    />
    <VistoriaFichaForm
      :form="form"
      :caminhoes="caminhoes"
      :pareceres="pareceres"
      :itens-estruturais="itensEstruturais"
      :itens-tanque="itensTanque"
      submit-label="Registrar Vistoria"
      @submit="submit"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import VistoriaFichaForm from '@/Components/Organisms/Tdap/VistoriaFichaForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  caminhoes: { type: Array, default: () => [] },
  pareceres: { type: Array, default: () => [] },
  itensEstruturais: { type: Array, default: () => [] },
  itensTanque: { type: Array, default: () => [] },
});

const base = {
  nome: '', edital: '', lote: '', placa_id: null, modelo: '', cor: '',
  data: new Date().toISOString().slice(0, 10), ano: '', capacidade: '',
  parecer: 'aprovada', ficha: '', observacoes: '',
};
[...props.itensEstruturais, ...props.itensTanque].forEach(k => {
  base[k] = false;
  base[`${k}_obs`] = '';
});

const form = useForm(base);

function submit() { form.post(route('tdap.vistorias.store')); }
function cancelar() { router.visit(route('tdap.vistorias.index')); }
</script>
