<template>
  <Head :title="`TDAP — Editar Vistoria #${v.id}`" />
  <div class="w-full space-y-6 pb-8">
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

// O resource vem embrulhado em `data` pelo Inertia; o fallback mantem a tela de
// pe caso o payload chegue plano (mesmo padrao do Show).
const v = props.vistoria.data ?? props.vistoria;

const base = {
  nome: v.nome || '', edital: v.edital || '',
  // `lote` saiu do formulario, mas continua no payload para nao ser apagado nas
  // vistorias legadas que ja o tinham preenchido.
  lote: v.lote ?? null,
  placa_id: v.placa_id, modelo: v.modelo || '', cor: v.cor || '',
  // `data_vistoria`, e nao `data`: `data` e propriedade reservada do useForm
  // (form.data()) e sobrescrevia o campo — ver o transform abaixo.
  data_vistoria: v.data || '', ano: v.ano || '', capacidade: v.capacidade ?? '',
  parecer: v.parecer ?? 'aprovada', ficha: v.ficha || '',
  lacre: v.lacre || '', observacoes: v.observacoes || '',
};
[...props.itensEstruturais, ...props.itensTanque].forEach(k => {
  base[k] = !!v[k];
  base[`${k}_obs`] = v[`${k}_obs`] || '';
});

const form = useForm(base);

// Devolve o nome `data` na requisicao: o contrato do backend nao muda.
form.transform(({ data_vistoria: dataVistoria, ...resto }) => ({ ...resto, data: dataVistoria }));

function submit() { form.put(route('tdap.vistorias.update', v.id)); }
function cancelar() { router.visit(route('tdap.vistorias.show', v.id)); }

// Datas vem como 'YYYY-MM-DD' (date puro). `new Date('2026-05-01')` e lido como
// meia-noite UTC e, no fuso do Brasil, exibia o dia anterior.
function fmtDate(d) {
  if (!d) return '';
  const iso = String(d).slice(0, 10);
  const [ano, mes, dia] = iso.split('-');

  return ano && mes && dia ? `${dia}/${mes}/${ano}` : '';
}
</script>
