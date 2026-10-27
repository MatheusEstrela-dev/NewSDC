<template>
  <Head title="TDAP — Nova Vistoria" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Nova Vistoria"
      description="Cadastra o caminhão-tanque e registra a primeira inspeção"
      :icon="TruckIcon"
    />
    <VistoriaFichaForm
      :form="form"
      novo-caminhao
      :prestadores="prestadores"
      :pareceres="pareceres"
      :itens-estruturais="itensEstruturais"
      :itens-tanque="itensTanque"
      submit-label="Registrar Vistoria"
      @submit="submit"
      @cancel="cancelar"
    >
      <template #antes-das-acoes>
        <VistoriaFotosNovas v-model="form.fotos" />
      </template>
    </VistoriaFichaForm>
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import VistoriaFichaForm from '@/Components/Organisms/Tdap/VistoriaFichaForm.vue';
import VistoriaFotosNovas from '@/Components/Organisms/Tdap/VistoriaFotosNovas.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  prestadores: { type: Array, default: () => [] },
  // Pre-selecao vinda da ficha do prestador (?prestador_id=). O backend so
  // devolve o id se ele estiver na lista de prestadores ativos.
  prestadorId: { type: Number, default: null },
  pareceres: { type: Array, default: () => [] },
  itensEstruturais: { type: Array, default: () => [] },
  itensTanque: { type: Array, default: () => [] },
});

// `toISOString()` devolve a data em UTC: depois das 21h no horario de Brasilia
// a vistoria nascia datada do dia seguinte. Aqui a data e montada no fuso local.
const hoje = new Date();
const hojeLocal = [
  hoje.getFullYear(),
  String(hoje.getMonth() + 1).padStart(2, '0'),
  String(hoje.getDate()).padStart(2, '0'),
].join('-');

// `data_vistoria`, e nao `data`: `data` e uma propriedade RESERVADA do useForm
// do Inertia. O transform devolve o nome `data` na requisicao.
// Modelo, cor, ano e capacidade sao digitados uma vez e o backend reparte
// entre o caminhao (`capacidade_m3`) e a vistoria (`capacidade`).
const base = {
  prestador_id: props.prestadorId,
  placa: '',
  marca: '',
  nome: '',
  edital: '',
  modelo: '',
  cor: '',
  data_vistoria: hojeLocal,
  ano: '',
  capacidade: '',
  parecer: 'aprovada',
  ficha: '',
  lacre: '',
  observacoes: '',
  fotos: [],
};
[...props.itensEstruturais, ...props.itensTanque].forEach((k) => {
  base[k] = false;
  base[`${k}_obs`] = '';
});

const form = useForm(base);

form.transform(({ data_vistoria: dataVistoria, ...resto }) => ({ ...resto, data: dataVistoria }));

function submit() {
  form.post(route('tdap.frota.vistorias.store-nova'));
}

function cancelar() {
  router.visit(route('tdap.frota.index'));
}
</script>
