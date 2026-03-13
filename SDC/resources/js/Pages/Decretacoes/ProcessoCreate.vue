<template>
    <div>
        <Head title="Nova Decretação" />
        <ProcessoCreateTemplate
          :form="form"
          :tipos-desastre="tiposDesastre"
          :cobrades="cobrades"
          :municipios="municipios"
          :redecs="redecs"
          :status-options="statusOptions"
          :analistas="analistas"
          @submit="handleSubmit"
          @cancel="handleCancel"
        />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProcessoCreateTemplate from '@/Templates/Decretacoes/ProcessoCreateTemplate.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  tiposDesastre: {
    type: Array,
    default: () => [],
  },
  cobrades: {
    type: Array,
    default: () => [],
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  redecs: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [
      { value: 'pendente', label: 'Pendente' },
      { value: 'em_analise', label: 'Em Analise' },
      { value: 'aprovado', label: 'Aprovado' },
      { value: 'rejeitado', label: 'Rejeitado' },
    ],
  },
  analistas: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  tipo_desastre_id: '',
  cobrade_id: '',
  origem: '',
  municipio_id: '',
  redec_id: '',
  situacao_anormalidade: '',
  data_entrada: '',
  data_ocorrencia: '',
  data_vencimento_decreto: '',
  status: 'pendente',
  analista_id: '',
  n_decreto_municipal: '',
  data_decreto_municipal: '',
  data_publicacao_decreto_municipal: '',
  prazo_vigencia_decreto: '',
  n_decreto_estadual: '',
  data_decreto_estadual: '',
  n_edicao_domg: '',
  data_publicacao_domg: '',
  n_portaria_federal: '',
  data_portaria_federal: '',
  n_edicao_dou: '',
  data_publicacao_dou: '',
  n_processo_sei: '',
  observacoes: '',
});

function handleSubmit() {
  form.post(route('decretacoes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('decretacoes.index'));
    },
  });
}

function handleCancel() {
  router.visit(route('decretacoes.index'));
}
</script>
