<template>
    <div>
        <Head :title="`Editar Dados do Desastre - ${processo?.n_protocolo_fide || 'Processo'}`" />
        <ProcessoDesastresEditTemplate
          :processo="processo"
          :municipios="municipios"
          :form="form"
          @submit="handleSubmit"
          @cancel="handleCancel"
        />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProcessoDesastresEditTemplate from '@/Templates/Decretacoes/ProcessoDesastresEditTemplate.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  processo: {
    type: Object,
    required: true,
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  filterOptions: {
    type: Object,
    default: () => ({}),
  },
});

const form = useForm({
  municipios: props.municipios || [],
});

function handleSubmit() {
  form.post(route('decretacoes.desastres.store', props.processo.id), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('decretacoes.show', props.processo.id));
    },
  });
}

function handleCancel() {
  router.visit(route('decretacoes.index'));
}
</script>
