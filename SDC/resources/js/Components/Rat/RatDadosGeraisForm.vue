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
      :loading="loading"
      :show-finalize="true"
      @save="$emit('save', localData)"
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
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['save', 'finalize', 'update:tem-vistoria', 'update:form-data']);

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
    data_comunicacao: props.rat?.comunicacao?.data_comunicacao || '',
    tipo_solicitacao: props.rat?.comunicacao?.tipo_solicitacao || '',
    telefone_contato: props.rat?.comunicacao?.telefone_contato || '',
    nome_solicitante: props.rat?.comunicacao?.nome_solicitante || '',
  },
  local: {
    pais_id: props.rat?.local?.pais_id || '1',
    uf: props.rat?.local?.uf || '',
    municipio_id: props.rat?.local?.municipio_id || '',
  },
  endereco: {
    cep: props.rat?.endereco?.cep || '',
    logradouro: props.rat?.endereco?.logradouro || '',
    numero: props.rat?.endereco?.numero || '',
    complemento: props.rat?.endereco?.complemento || '',
    bairro: props.rat?.endereco?.bairro || '',
    km: props.rat?.endereco?.km || '',
    cruzamento: props.rat?.endereco?.cruzamento || '',
    ponto_referencia: props.rat?.endereco?.ponto_referencia || '',
    tipo_localizacao: props.rat?.endereco?.tipo_localizacao || '',
    latitude: props.rat?.endereco?.latitude || null,
    longitude: props.rat?.endereco?.longitude || null,
  },
});

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
