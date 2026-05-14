<template>
  <Head :title="`TDAP — Cronograma ${c.numero}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar Cronograma ${c.numero}`"
      :description="`${fmtDate(c.dt_inicio)} — ${fmtDate(c.dt_final)}`"
      :icon="TruckIcon"
    />
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-500/30 rounded-md p-4 text-sm text-amber-800 dark:text-amber-200">
      Apenas cronogramas em rascunho podem ser editados. Para alterar um cronograma ativo, encerre-o e crie um novo.
    </div>
    <CronogramaForm
      :form="form"
      :atas="atas"
      :lotes="lotes"
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
import CronogramaForm from '@/Components/Organisms/Tdap/CronogramaForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  cronograma:  { type: Object, required: true },
  atas:        { type: Array, default: () => [] },
  lotes:       { type: Array, default: () => [] },
  municipios:  { type: Array, default: () => [] },
  prestadores: { type: Array, default: () => [] },
});

const c = props.cronograma.data;

const form = useForm({
  numero: c.numero || '',
  empenho: c.empenho || '',
  ata_id: c.ata_id,
  lote_id: c.lote_id,
  municipio_id: c.municipio_id,
  prestador_id: c.prestador_id,
  cnpj: c.cnpj,
  consumo_diario: c.consumo_diario ?? '',
  dias: c.dias ?? '',
  fator: c.fator ?? 1,
  dt_inicio: c.dt_inicio || '',
  dt_final: c.dt_final || '',
  justificativa: c.justificativa || '',
  nota_empenho: c.nota_empenho || '',
  ponto_captacao_id: c.ponto_captacao_id,
  observacao: c.observacao || '',
});

function submit() { form.put(route('tdap.cronogramas.update', c.id)); }
function cancelar() { router.visit(route('tdap.cronogramas.show', c.id)); }

function fmtDate(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
