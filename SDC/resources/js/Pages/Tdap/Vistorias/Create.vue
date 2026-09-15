<template>
  <Head title="TDAP — Nova Vistoria" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Nova Vistoria"
      :description="`Inspeção técnica do caminhão-tanque (${itensEstruturais.length} + ${itensTanque.length} itens)`"
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
  caminhoes: { type: Array, default: () => [] },
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
// do Inertia (o metodo form.data(), definido depois do spread dos campos,
// sobrescrevia o campo). O DatePicker recebia a funcao no lugar da data, quebrava
// no setup e o campo simplesmente nao aparecia na tela; no envio a data ia
// vazia. O transform devolve o nome `data` na requisicao, sem mexer no backend.
const base = {
  nome: '', edital: '', placa_id: null, modelo: '', cor: '',
  data_vistoria: hojeLocal, ano: '', capacidade: '',
  parecer: 'aprovada', ficha: '', lacre: '', observacoes: '',
  // Os File ficam aqui ate o submit. O Inertia detecta arquivos no payload e
  // troca sozinho para multipart/form-data.
  fotos: [],
};
[...props.itensEstruturais, ...props.itensTanque].forEach(k => {
  base[k] = false;
  base[`${k}_obs`] = '';
});

const form = useForm(base);

form.transform(({ data_vistoria: dataVistoria, ...resto }) => ({ ...resto, data: dataVistoria }));

function submit() { form.post(route('tdap.vistorias.store')); }
function cancelar() { router.visit(route('tdap.vistorias.index')); }
</script>
