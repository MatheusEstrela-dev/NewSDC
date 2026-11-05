<template>
  <Head title="Nova nota técnica" />

  <div class="w-full space-y-6 pb-8">
    <PageHeader
      title="Nova nota técnica"
      description="A nota nasce como rascunho: só quem edita a vê até ser publicada."
      :icon-image="moduleIcon('notas-tecnicas') ?? ''"
      variant="gradient"
      :espaco-inferior="false"
    />

    <form class="max-w-3xl space-y-6" @submit.prevent="salvar">
      <NotaTecnicaFormSecoes
        :form="form"
        :errors="form.errors"
        :categorias="categorias"
        :telas="telas"
        :limite-mb="limiteMb"
        com-arquivo
      />

      <FormActions
        submit-label="Cadastrar rascunho"
        :loading="form.processing"
        :disabled="form.processing"
        @cancel="voltar"
        @submit="salvar"
      />
    </form>
  </div>
</template>

<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import NotaTecnicaFormSecoes from '@/Components/Organisms/NotasTecnicas/NotaTecnicaFormSecoes.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  categorias: { type: Array, default: () => [] },
  telas: { type: Array, default: () => [] },
  limiteMb: { type: Number, default: 30 },
  categoriaSugerida: { type: Number, default: null },
});

const form = useForm({
  titulo: '',
  categoria_id: props.categoriaSugerida ?? '',
  resumo: '',
  vigencia_inicio: '',
  rotas_relacionadas: [],
  arquivo: null,
  numero: '1.0',
  changelog: '',
});

function salvar() {
  form.post(route('notas-tecnicas.store'), { forceFormData: true, preserveScroll: true });
}

function voltar() {
  router.visit(route('notas-tecnicas.index'));
}
</script>
