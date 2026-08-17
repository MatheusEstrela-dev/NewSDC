<template>
  <Head title="TDAP — Novo Lote" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="Novo Lote"
      description="Cadastrar lote de fornecimento (ata + municípios + prestador)"
      :icon="MapIcon"
    />
    <LoteForm
      :form="form"
      :atas="atas"
      :municipios="municipios"
      :prestadores="prestadores"
      submit-label="Cadastrar"
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
  ata_id:      { type: Number, default: null },
  atas:        { type: Array, default: () => [] },
  municipios:  { type: Array, default: () => [] },
  prestadores: { type: Array, default: () => [] },
});

const form = useForm({
  ata_id: props.ata_id,
  municipio_ids: [],
  prestador_id: null,
  numero: '',
  nome: '',
  contrato: '',
  qtd_agua_m3: '',
  valor_m3: '',
  ativo: true,
  observacoes: '',
});

function submit() { form.post(route('tdap.lotes.store')); }
function cancelar() { router.visit(route('tdap.lotes.index')); }
</script>
