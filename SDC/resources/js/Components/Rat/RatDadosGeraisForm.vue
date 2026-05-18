<template>
  <fieldset
    :disabled="viewOnly"
    style="border: none; padding-top: 0; padding-left: 0; padding-right: 0; margin: 0; min-width: 0;"
    :class="['space-y-4 sm:space-y-6 rat-form-content', viewOnly ? 'pb-10' : 'pb-6']"
  >
    <RatAtendimentoSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatNaturezaSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatCommunicationSection
      v-model="localData.comunicacao"
    />

    <RatConfigSection
      :model-value="localData.dadosGerais"
      @update:model-value="onDadosGeraisUpdate($event)"
    />

    <RatLocationSection
      v-model="localData.local"
    />

    <RatAddressSection
      v-model="localData.endereco"
      @location-updated="handleLocationUpdated"
    />

    <RatFormActions
      :view-only="viewOnly"
      :loading="loading"
      label="Salvar Dados Gerais"
      @save="$emit('save', localData)"
    />
  </fieldset>
</template>

<script setup>
import { ref, watch } from 'vue';
import RatAtendimentoSection from './Sections/RatAtendimentoSection.vue';
import RatCommunicationSection from './Sections/RatCommunicationSection.vue';
import RatNaturezaSection from './Sections/RatNaturezaSection.vue';
import RatConfigSection from './Sections/RatConfigSection.vue';
import RatLocationSection from './Sections/RatLocationSection.vue';
import RatAddressSection from './Sections/RatAddressSection.vue';
import RatFormActions from '@/Components/Molecules/Rat/RatFormActions.vue';

const props = defineProps({
  rat: {
    type: [Object, null],
    default: () => null,
  },
  savedFormData: {
    type: [Object, null],
    default: null,
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save', 'update:tem-vistoria', 'update:form-data']);

const _dg  = props.savedFormData?.dadosGerais ?? props.rat?.dados_gerais  ?? {};
const _com = props.savedFormData?.comunicacao ?? props.rat?.comunicacao   ?? {};
const _loc = props.savedFormData?.local       ?? props.rat?.local         ?? {};
const _end = props.savedFormData?.endereco    ?? props.rat?.endereco      ?? {};

const localData = ref({
  dadosGerais: {
    data_fato:              _dg.data_fato              || '',
    data_inicio_atividade:  _dg.data_inicio_atividade  || '',
    data_termino_atividade: _dg.data_termino_atividade || '',
    nat_codigo:             _dg.nat_codigo             || '',
    nat_cobrade_id:         _dg.nat_cobrade_id         || '',
    nat_ocorrencia:         _dg.nat_ocorrencia         || '',
    nat_nome_operacao:      _dg.nat_nome_operacao      || '',
    uni_responsavel_municipio: _dg.uni_responsavel_municipio || '',
    uni_responsavel_codigo:    _dg.uni_responsavel_codigo    || '',
    uni_responsavel_unidade:   _dg.uni_responsavel_unidade   || '',
    uni_bo_ano:             _dg.uni_bo_ano             || '',
    uni_bo_sequencial:      _dg.uni_bo_sequencial      || '',
    uni_bo_cod_unidade:     _dg.uni_bo_cod_unidade     || '',
    descricao:              _dg.descricao              || '',
    tem_vistoria: _dg.tem_vistoria ?? props.rat?.tem_vistoria ?? false,
  },
  comunicacao: {
    data_comunicacao: _com.data_comunicacao || _com.com_ocorrencia_data || '',
    tipo_solicitacao: _com.tipo_solicitacao || _com.com_ocorrencia_atendimento || '',
    telefone_contato: _com.telefone_contato || _com.com_telefone_contato || '',
    nome_solicitante: _com.nome_solicitante || _com.com_nome_solicitante || '',
  },
  local: {
    pais: _loc.pais ?? _loc.pais_id ?? 'BR',
    uf: _loc.uf || '',
    municipio_id: _loc.municipio_id || '',
  },
  endereco: {
    cep: _end.cep || '',
    logradouro: _end.logradouro || '',
    numero: _end.numero || '',
    complemento: _end.complemento || '',
    bairro: _end.bairro || '',
    km: _end.km || '',
    cruzamento: _end.cruzamento || '',
    ponto_referencia: _end.ponto_referencia || '',
    tipo_localizacao: _end.tipo_localizacao || '',
    latitude: _end.latitude || null,
    longitude: _end.longitude || null,
  },
});

function onDadosGeraisUpdate(newDg) {
  const hadVistoria = localData.value.dadosGerais.tem_vistoria;
  localData.value.dadosGerais = newDg;
  if (newDg.tem_vistoria !== hadVistoria) {
    emit('update:tem-vistoria', newDg.tem_vistoria);
  }
}

function handleLocationUpdated({ uf }) {
  if (uf) localData.value.local.uf = uf;
}

watch(
  () => props.rat,
  (newVal) => {
    if (newVal?.dados_gerais) {
      localData.value.dadosGerais = {
        ...localData.value.dadosGerais,
        ...newVal.dados_gerais,
        tem_vistoria: newVal.tem_vistoria ?? localData.value.dadosGerais.tem_vistoria,
      };
    }
  },
  { deep: true }
);

watch(
  localData,
  (newVal) => {
    emit('update:form-data', { ...newVal });
  },
  { deep: true, immediate: true }
);
</script>

<style scoped>
.rat-form-content {
  padding-bottom: 0.5rem;
}
</style>
