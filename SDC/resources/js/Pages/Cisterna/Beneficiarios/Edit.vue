<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${beneficiario.nome}`" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
      <PageHeader
        title="Editar cadastro"
        :description="beneficiario.nome"
        :icon-image="moduleIcon('cisternas')"
        variant="gradient"
      >
        <template #actions>
          <Link :href="route('cisternas.beneficiarios.show', beneficiario.id)" :class="VOLTAR">Voltar</Link>
        </template>
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
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import BeneficiarioForm from '@/Components/Organisms/Cisterna/BeneficiarioForm.vue';
import { useBeneficiarioForm } from '@/Composables/cisterna/useBeneficiarioForm';

const props = defineProps({
  beneficiario: { type: Object, required: true },
  opcoes: { type: Object, default: () => ({}) },
});

const VOLTAR = 'rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-800';

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
