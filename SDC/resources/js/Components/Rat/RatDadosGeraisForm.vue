<template>
  <fieldset
    :disabled="viewOnly"
    style="border: none; padding: 0; margin: 0; min-width: 0;"
    :class="['space-y-4 sm:space-y-6 rat-form-content', viewOnly ? 'pb-10' : '']"
  >
    <RatAtendimentoSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatCommunicationSection
      v-model="localData.comunicacao"
    />

    <RatNaturezaSection
      :model-value="localData.dadosGerais"
      @update:model-value="localData.dadosGerais = $event"
    />

    <RatConfigSection
      :model-value="localData.dadosGerais"
      @update:model-value="onConfigUpdate"
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
      @save-draft="$emit('save-draft', localData)"
      @finalize="$emit('finalize', localData)"
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
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save-draft', 'finalize', 'update:tem-vistoria', 'update:form-data']);

const localData = ref({
  dadosGerais: {
    data_fato: props.rat?.dados_gerais?.data_fato || '',
    data_inicio_atividade: props.rat?.dados_gerais?.data_inicio_atividade || '',
    data_termino_atividade: props.rat?.dados_gerais?.data_termino_atividade || '',
    nat_cobrade_id: props.rat?.dados_gerais?.nat_cobrade_id || '',
    nat_nome_operacao: props.rat?.dados_gerais?.nat_nome_operacao || '',
    tem_vistoria: props.rat?.tem_vistoria || false,
  },
  comunicacao: {
    com_ocorrencia_data: props.rat?.comunicacao?.com_ocorrencia_data || '',
    com_ocorrencia_atendimento: props.rat?.comunicacao?.com_ocorrencia_atendimento || '',
    telefone_contato: props.rat?.comunicacao?.telefone_contato || '',
    nome_solicitante: props.rat?.comunicacao?.nome_solicitante || '',
  },
  local: {
    local_pais: props.rat?.local?.local_pais || 'BR',
    local_uf: props.rat?.local?.local_uf || '',
    local_municipio: props.rat?.local?.local_municipio || '',
  },
  endereco: {
    local_cep: props.rat?.endereco?.local_cep || '',
    local_logradouro: props.rat?.endereco?.local_logradouro || '',
    local_numero: props.rat?.endereco?.local_numero || '',
    local_complemento: props.rat?.endereco?.local_complemento || '',
    local_bairro: props.rat?.endereco?.local_bairro || '',
    local_km: props.rat?.endereco?.local_km || '',
    local_cruzamento: props.rat?.endereco?.local_cruzamento || '',
    local_ponto_referencia: props.rat?.endereco?.local_ponto_referencia || '',
    local_latitude: props.rat?.endereco?.local_latitude || null,
    local_longitude: props.rat?.endereco?.local_longitude || null,
  },
});

// Converte datetime-local para formato dd/mm/aaaa hh:mm
const formatDateTimeForBackend = (isoDateTime) => {
  if (!isoDateTime) return '';
  if (typeof isoDateTime !== 'string') return '';
  const [date, time] = isoDateTime.split('T');
  if (!date || !time) return '';
  const [year, month, day] = date.split('-');
  return `${day}/${month}/${year} ${time}`;
};

// Mapeia e flattena os dados do formulário para o formato esperado pelo backend
const transformDataForBackend = (data) => {
  const transformed = {
    // Dados Gerais
    data_fato: formatDateTimeForBackend(data.dadosGerais?.data_fato),
    data_inicio_atividade: formatDateTimeForBackend(data.dadosGerais?.data_inicio_atividade),
    data_termino_atividade: formatDateTimeForBackend(data.dadosGerais?.data_termino_atividade),
    nat_cobrade_id: data.dadosGerais?.nat_cobrade_id,
    nat_nome_operacao: data.dadosGerais?.nat_nome_operacao,
    tem_vistoria: data.dadosGerais?.tem_vistoria || false,

    // Comunicação
    com_data_comunicacao: formatDateTimeForBackend(data.comunicacao?.data_comunicacao),
    com_tipo_atendimento: data.comunicacao?.tipo_solicitacao || 'outro',

    // Localização
    local_pais: data.local?.pais_id || 'BR',
    local_uf: data.local?.uf || '',
    local_municipio: data.local?.municipio_id || '',

    // Endereço detalhado
    local_logradouro: data.endereco?.logradouro || '',
    local_numero: data.endereco?.numero || '',
    local_bairro: data.endereco?.bairro || '',
    local_cep: data.endereco?.cep || '',
    local_complemento: data.endereco?.complemento || '',
    local_km: data.endereco?.km || '',
    local_cruzamento: data.endereco?.cruzamento || '',
    local_ponto_referencia: data.endereco?.ponto_referencia || '',
    local_latitude: data.endereco?.latitude || null,
    local_longitude: data.endereco?.longitude || null,
  };

  return transformed;
};

function onConfigUpdate(dadosGerais) {
  const hadVistoria = localData.value.dadosGerais.tem_vistoria;
  localData.value.dadosGerais = dadosGerais;
  if (dadosGerais.tem_vistoria !== hadVistoria) {
    emit('update:tem-vistoria', dadosGerais.tem_vistoria);
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
    // Emite dados estruturados (não transformados) para criação de novo RAT
    // A transformação acontecerá apenas ao atualizar dados gerais com um RAT já existente
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
