<template>
    <div>
        <Head :title="`Editar Processo - ${processo?.n_protocolo_fide || 'Processo'}`" />
        <ProcessoCreateTemplate
          :form="form"
          :tipos-desastre="tiposDesastre"
          :cobrades="cobrades"
          :municipios="municipios"
          :redecs="redecs"
          :status-options="statusOptions"
          :analistas="analistas"
          :is-editing="true"
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
  processo: {
    type: Object,
    required: true,
  },
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
    default: () => [],
  },
  analistas: {
    type: Array,
    default: () => [],
  },
});

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';
  return d.toISOString().split('T')[0];
};

const form = useForm({
  tipo_desastre_id: props.processo?.tipo_desastre_id || '',
  cobrade_id: props.processo?.tipo_desastre_id || '',
  origem: props.processo?.processo || '',
  municipio_id: props.processo?.municipios?.[0]?.id || '',
  redec_id: props.processo?.redec_id || '',
  situacao_anormalidade: props.processo?.tipo_desastre || props.processo?.situacao_anormalidade || '',
  data_entrada: formatDate(props.processo?.data_entrada),
  data_ocorrencia: formatDate(props.processo?.data_ocorrencia_desastre),
  data_vencimento_decreto: formatDate(props.processo?.data_vencimento),
  status: props.processo?.status || 'pendente',
  analista_id: props.processo?.analista || '',
  n_protocolo_fide: props.processo?.n_protocolo_fide || '',
  n_decreto_municipal: props.processo?.decreto_municipal || '',
  data_decreto_municipal: formatDate(props.processo?.data_decreto_municipal),
  data_publicacao_decreto_municipal: formatDate(props.processo?.data_publicacao_mg),
  prazo_vigencia_decreto: props.processo?.prazo_vigencia || '',
  n_decreto_estadual: props.processo?.n_decreto_estadual || '',
  data_decreto_estadual: formatDate(props.processo?.data_decreto_estadual),
  n_edicao_domg: props.processo?.n_edicao_domg || '',
  data_publicacao_domg: formatDate(props.processo?.data_publicacao_mg),
  n_portaria_federal: props.processo?.portaria_reconhecimento_fed || '',
  data_portaria_federal: formatDate(props.processo?.data_portaria_federal),
  n_edicao_dou: props.processo?.portaria_diario_oficial || '',
  data_publicacao_dou: formatDate(props.processo?.data_publicacao_diario),
  n_processo_sei: props.processo?.processo_inserido_sei || '',
  observacoes: props.processo?.observacoes || '',
});

function handleSubmit() {
  form.put(route('decretacoes.update', props.processo.id), {
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
