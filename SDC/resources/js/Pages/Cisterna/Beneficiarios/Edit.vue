<template>
  <AuthenticatedLayout>
    <Head :title="`Cisternas — ${beneficiario.nome}`" />

    <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
      <header>
        <Link
          :href="route('cisternas.beneficiarios.show', beneficiario.id)"
          class="text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200"
        >
          &larr; {{ beneficiario.nome }}
        </Link>
        <h1 class="mt-1 text-xl font-bold text-slate-900 dark:text-slate-100">Editar cadastro</h1>
      </header>

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
