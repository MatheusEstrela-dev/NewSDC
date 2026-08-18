<template>
  <Head :title="`TDAP — Lote ${l.numero}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar Lote ${l.numero}`"
      :description="(l.municipios ?? []).map(m => m.nome).join(', ')"
      :icon="MapIcon"
    />
    <LoteForm
      :form="form"
      :atas="atas"
      :municipios="municipios"
      :prestadores="prestadores"
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
import LoteForm from '@/Components/Organisms/Tdap/LoteForm.vue';
import MapIcon from '@/Components/Icons/MapIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  lote:        { type: Object, required: true },
  atas:        { type: Array, default: () => [] },
  municipios:  { type: Array, default: () => [] },
  prestadores: { type: Array, default: () => [] },
});

const l = props.lote.data;

const form = useForm({
  ata_id: l.ata_id,
  municipio_ids: [...(l.municipio_ids ?? [])],
  prestador_id: l.prestador_id,
  numero: l.numero || '',
  nome: l.nome || '',
  contrato: l.contrato || '',
  qtd_agua_m3: l.qtd_agua_m3 ?? '',
  valor_m3: l.valor_m3 ?? '',
  ativo: l.ativo ?? true,
  observacoes: l.observacoes || '',
});

function submit() { form.put(route('tdap.lotes.update', l.id)); }
function cancelar() { router.visit(route('tdap.lotes.show', l.id)); }
</script>
