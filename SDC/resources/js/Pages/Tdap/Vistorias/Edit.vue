<template>
  <Head :title="`TDAP — Editar Vistoria #${v.id}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar Vistoria #${v.id}`"
      :description="`${v.caminhao?.placa ?? ''} — ${fmtDate(v.data)}`"
      :icon="TruckIcon"
    />
    <VistoriaFichaForm
      :form="form"
      :caminhoes="caminhoes"
      :pareceres="pareceres"
      :itens-estruturais="itensEstruturais"
      :itens-tanque="itensTanque"
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
import VistoriaFichaForm from '@/Components/Organisms/Tdap/VistoriaFichaForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  vistoria: { type: Object, required: true },
  caminhoes: { type: Array, default: () => [] },
  pareceres: { type: Array, default: () => [] },
  itensEstruturais: { type: Array, default: () => [] },
  itensTanque: { type: Array, default: () => [] },
});

const v = props.vistoria.data;

const base = {
  nome: v.nome || '', edital: v.edital || '', lote: v.lote || '',
  placa_id: v.placa_id, modelo: v.modelo || '', cor: v.cor || '',
  data: v.data || '', ano: v.ano || '', capacidade: v.capacidade ?? '',
  parecer: v.parecer ?? 'aprovada', ficha: v.ficha || '', observacoes: v.observacoes || '',
};
[...props.itensEstruturais, ...props.itensTanque].forEach(k => {
  base[k] = !!v[k];
  base[`${k}_obs`] = v[`${k}_obs`] || '';
});

const form = useForm(base);

function submit() { form.put(route('tdap.vistorias.update', v.id)); }
function cancelar() { router.visit(route('tdap.vistorias.show', v.id)); }

function fmtDate(d) {
  if (!d) return '';
  return new Date(d).toLocaleDateString('pt-BR');
}
</script>
