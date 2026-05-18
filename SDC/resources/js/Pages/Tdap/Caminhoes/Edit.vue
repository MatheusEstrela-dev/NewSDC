<template>
  <Head :title="`TDAP — ${caminhao.data.placa}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Editar: ${caminhao.data.placa}`"
      :description="`${caminhao.data.marca || ''} ${caminhao.data.modelo || ''}`.trim() || 'Caminhão-tanque'"
      :icon="TruckIcon"
    />
    <CaminhaoForm
      :form="form"
      :prestadores="prestadores"
      submit-label="Salvar alterações"
      @submit="submit"
      @cancel="cancelar"
    />
  </div>
</template>

<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import CaminhaoForm from '@/Components/Organisms/Tdap/CaminhaoForm.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  caminhao:    { type: Object, required: true },
  prestadores: { type: Array, default: () => [] },
});

const c = props.caminhao.data;

const form = useForm({
  prestador_id: c.prestador_id,
  placa: c.placa || '',
  marca: c.marca || '',
  modelo: c.modelo || '',
  cor: c.cor || '',
  ano: c.ano || '',
  capacidade_m3: c.capacidade_m3 ?? '',
  ativo: c.ativo ?? true,
  observacoes: c.observacoes || '',
});

function submit() {
  form.put(route('tdap.caminhoes.update', c.id));
}

function cancelar() {
  router.visit(route('tdap.caminhoes.show', c.id));
}
</script>
