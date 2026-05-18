<template>
    <div>
        <Head :title="pageTitle" />
        <ProcessoCreateTemplate
          :form="form"
          :tipos-desastre="tiposDesastre"
          :cobrades="cobrades"
          :municipios="municipios"
          :redecs="redecs"
          :status-options="statusOptions"
          :analistas="analistas"
          :processo="processoData"
          :municipios-desastres="municipiosDesastres"
          :is-editing="true"
          :view-only="viewOnly"
          @submit="handleSubmit"
          @cancel="handleCancel"
        />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProcessoCreateTemplate from '@/Templates/Decretacoes/ProcessoCreateTemplate.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
  municipiosDesastres: {
    type: Array,
    default: () => [],
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const formatDate = (date) => {
  if (!date) return '';
  const d = new Date(date);
  if (isNaN(d.getTime())) return '';
  return d.toISOString().split('T')[0];
};

const processoData = props.processo?.data || props.processo;

const pageTitle = computed(() => {
  const protocolo = processoData?.n_protocolo_fide || 'Processo';
  return props.viewOnly
    ? `Visualizar Processo - ${protocolo}`
    : `Editar Processo - ${protocolo}`;
});

const form = useForm({
  tipo_desastre_id: processoData?.tipo_desastre_id || '',
  cobrade_id: processoData?.cobrade_id || processoData?.tipo_desastre_id || '',
  origem: processoData?.origem || (processoData?.processo?.toLowerCase()) || '',
  municipio_id: processoData?.municipio_id || processoData?.municipios?.[0]?.id || '',
  redec_id: processoData?.redec_id || '',
  situacao_anormalidade: processoData?.situacao_anormalidade || processoData?.tipo_desastre || '',
  data_entrada: formatDate(processoData?.data_entrada),
  data_ocorrencia: formatDate(processoData?.data_ocorrencia_desastre || processoData?.data_ocorrencia),
  data_vencimento_decreto: formatDate(processoData?.data_vencimento),
  status: processoData?.status || 'pendente',
  analista_id: processoData?.analista || '',
  n_protocolo_fide: processoData?.n_protocolo_fide || processoData?.protocolo_fide || '',
  n_decreto_municipal: processoData?.decreto_municipal || '',
  data_decreto_municipal: formatDate(processoData?.data_decreto_municipal),
  data_publicacao_decreto_municipal: formatDate(processoData?.data_publicacao_mg),
  prazo_vigencia_decreto: processoData?.prazo_vigencia || '',
  n_decreto_estadual: processoData?.n_decreto_estadual || '',
  data_decreto_estadual: formatDate(processoData?.data_decreto_estadual),
  n_edicao_domg: processoData?.n_edicao_domg || '',
  data_publicacao_domg: formatDate(processoData?.data_publicacao_domg || processoData?.data_publicacao_mg),
  n_portaria_federal: processoData?.n_portaria_federal || processoData?.portaria_reconhecimento_fed || '',
  data_portaria_federal: formatDate(processoData?.data_portaria_federal),
  n_edicao_dou: processoData?.n_edicao_dou || processoData?.portaria_diario_oficial || '',
  data_publicacao_dou: formatDate(processoData?.data_publicacao_diario),
  n_processo_sei: processoData?.processo_inserido_sei || '',
  observacoes: processoData?.observacoes || '',
});

function handleSubmit() {
  if (props.viewOnly) return;
  const pId = processoData?.id;
  form.put(route('decretacoes.update', pId), {
    preserveScroll: true,
    onSuccess: () => {
      router.visit(route('decretacoes.show', pId));
    },
  });
}

function handleCancel() {
  router.visit(route('decretacoes.index'));
}
</script>
