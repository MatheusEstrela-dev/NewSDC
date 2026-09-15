<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${beneficiario.nome}`" />

    <div class="w-full space-y-6 pb-8">
      <PageHeader
        title="Editar cadastro"
        :description="beneficiario.nome"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
        :espaco-inferior="false"
      >
      </PageHeader>

      <BeneficiarioForm
        modo="editar"
        :form="form"
        :opcoes="opcoes"
        :comunidades="comunidades"
        :carregando-comunidades="carregandoComunidades"
        :comprovantes="beneficiario.comprovantes ?? []"
        :processando="form.processing"
        @municipio="carregarComunidades"
        @arquivo="anexar"
        @submit="salvar"
        @cancel="cancelar"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import BeneficiarioForm from '@/Components/Organisms/Cisterna/BeneficiarioForm.vue';
import { useBeneficiarioForm } from '@/Composables/cisterna/useBeneficiarioForm';

const props = defineProps({
  beneficiario: { type: Object, required: true },
  opcoes: { type: Object, default: () => ({}) },
});

const {
  form,
  comunidades,
  carregandoComunidades,
  carregarComunidades,
  anexar,
  salvar,
  cancelar,
} = useBeneficiarioForm(props.beneficiario);

/**
 * O controller manda `opcoes.comunidades` do municipio atual. Semear a lista com
 * ela evita um round-trip e, mais importante, evita o select abrir vazio por um
 * instante com a comunidade JA escolhida -- o que parece perda de dado.
 */
onMounted(() => {
  const doServidor = props.opcoes?.comunidades ?? [];

  if (doServidor.length > 0) {
    comunidades.value = doServidor;

    return;
  }

  if (props.beneficiario?.municipio?.id) {
    carregarComunidades(props.beneficiario.municipio.id);
  }
});
</script>
