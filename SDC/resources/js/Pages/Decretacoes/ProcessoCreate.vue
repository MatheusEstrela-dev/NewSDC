<template>
    <div>
        <Head :title="processo?.id ? `Novo Reconhecimento - ${processo.n_protocolo_fide || 'Em andamento'}` : 'Nova Decretacao'" />
        <ProcessoCreateTemplate
          :form="form"
          :tipos-desastre="tiposDesastre"
          :cobrades="cobrades"
          :municipios="municipios"
          :redecs="redecs"
          :status-options="statusOptions"
          :analistas="analistas"
          :processo="processo"
          :municipios-desastres="municipiosDesastres"
          @submit="handleSubmit"
          @submit-desastres="handleSubmitDesastres"
          @cancel="handleCancel"
          @finish="handleFinish"
        />
    </div>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProcessoCreateTemplate from '@/Templates/Decretacoes/ProcessoCreateTemplate.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  tiposDesastre:        { type: Array,  default: () => [] },
  cobrades:             { type: Array,  default: () => [] },
  municipios:           { type: Array,  default: () => [] },
  redecs:               { type: Array,  default: () => [] },
  statusOptions:        { type: Array,  default: () => [] },
  analistas:            { type: Array,  default: () => [] },
  // Quando o backend retorna ?id={newId} apos store(): processo recem-criado.
  processo:             { type: Object, default: null },
  // Arvore de municipios + categorias + items + campos para a Aba 2 do wizard.
  municipiosDesastres:  { type: Array,  default: () => [] },
});

const { show: toast } = useToast();

// Hidrata o form com os dados do processo recem-criado (se houver), permitindo
// que o usuario continue editando a Aba 1 sem perder o que ja foi salvo.
const form = useForm({
  tipo_desastre_id:                 props.processo?.tipo_desastre_id ?? '',
  cobrade_id:                       props.processo?.cobrade_id ?? '',
  origem:                           props.processo?.origem ?? props.processo?.processo ?? '',
  municipio_id:                     props.processo?.municipio_id ?? '',
  redec_id:                         props.processo?.redec_id ?? '',
  situacao_anormalidade:            props.processo?.situacao_anormalidade ?? '',
  data_entrada:                     props.processo?.data_entrada ?? '',
  data_ocorrencia:                  props.processo?.data_ocorrencia ?? '',
  data_vencimento_decreto:          props.processo?.data_vencimento ?? '',
  status:                           props.processo?.status ?? 'pendente',
  analista_id:                      props.processo?.analista ?? '',
  n_protocolo_fide:                 props.processo?.n_protocolo_fide ?? '',
  n_decreto_municipal:              props.processo?.decreto_municipal ?? '',
  data_decreto_municipal:           props.processo?.data_decreto_municipal ?? '',
  data_publicacao_decreto_municipal:props.processo?.data_publicacao_mg ?? '',
  prazo_vigencia_decreto:           props.processo?.prazo_vigencia ?? '',
  n_decreto_estadual:               props.processo?.n_decreto_estadual ?? '',
  data_decreto_estadual:            props.processo?.data_decreto_estadual ?? '',
  n_edicao_domg:                    props.processo?.n_edicao_domg ?? '',
  data_publicacao_domg:             props.processo?.data_publicacao_domg ?? '',
  n_portaria_federal:               props.processo?.n_portaria_federal ?? '',
  data_portaria_federal:            props.processo?.data_portaria_federal ?? '',
  n_edicao_dou:                     props.processo?.n_edicao_dou ?? '',
  data_publicacao_dou:              props.processo?.data_publicacao_diario ?? '',
  n_processo_sei:                   props.processo?.processo_inserido_sei ?? '',
  observacoes:                      props.processo?.observacoes ?? '',
});

function handleSubmit() {
  // Se ja existe processo (post-store), Aba 1 atualiza; senao, cria.
  if (props.processo?.id) {
    form.put(route('decretacoes.update', props.processo.id), {
      preserveScroll: true,
      onSuccess: () => toast('Identificacao atualizada.'),
    });
    return;
  }

  form.post(route('decretacoes.store'), {
    preserveScroll: true,
    onSuccess: () => {
      toast('Decretacao criada com sucesso. Agora preencha os dados do desastre.');
      // Redirect para /create?id={newId} feito pelo backend - permanece em CREATE.
    },
  });
}

function handleSubmitDesastres(payloadMunicipios) {
  if (!props.processo?.id) {
    return;
  }
  router.post(
    route('decretacoes.desastres.store', props.processo.id),
    { municipios: payloadMunicipios },
    {
      preserveScroll: true,
      onSuccess: () => toast('Dados de desastre salvos com sucesso.'),
    },
  );
}

function handleFinish() {
  router.visit(route('decretacoes.index'));
}

function handleCancel() {
  router.visit(route('decretacoes.index'));
}
</script>
