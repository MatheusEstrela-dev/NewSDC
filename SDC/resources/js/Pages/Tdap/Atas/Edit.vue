<template>
  <Head :title="`TDAP — Ata ${a.numero}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="`Editar Ata: ${a.numero}`"
      :description="`${formatDate(a.dt_inicio)} — ${formatDate(a.dt_final)}`"
      :icon="CalendarIcon"
    />
    <AtaForm :form="form" submit-label="Salvar alterações" @submit="submit" @cancel="cancelar" />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import AtaForm from '@/Components/Organisms/Tdap/AtaForm.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  ata: { type: Object, required: true },
});

const a = props.ata.data;

const form = useForm({
  numero: a.numero || '',
  dt_inicio: a.dt_inicio || '',
  dt_final: a.dt_final || '',
  historico: a.historico || '',
  ativo: a.ativo ?? true,
  observacoes: a.observacoes || '',
});

function submit() { form.put(route('tdap.atas.update', a.id)); }
function cancelar() { router.visit(route('tdap.atas.show', a.id)); }

function formatDate(d) {
  if (!d) return '';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
