<template>
  <div class="space-y-6 rat-form-content">
    <!-- Seção: Atendimento -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Atendimento</h3>
          <p class="text-xs text-slate-500 mt-0.5">Informações sobre data e horário do fato e atividades</p>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="rat-grid-3">
          <FormField
            label="Data/Hora do Fato"
            type="datetime-local"
            v-model="localData.dadosGerais.data_fato"
            required
          />
          <FormField
            label="Início da Atividade"
            type="datetime-local"
            v-model="localData.dadosGerais.data_inicio_atividade"
            required
          />
          <FormField
            label="Término da Atividade"
            type="datetime-local"
            v-model="localData.dadosGerais.data_termino_atividade"
          />
        </div>
      </div>
    </div>

    <!-- Seção: Comunicação da Ocorrência -->
    <RatCommunicationSection
      v-model="localData.comunicacao"
    />

    <!-- Seção: Natureza da Ocorrência -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-success">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Natureza da Ocorrência</h3>
          <p class="text-xs text-slate-500 mt-0.5">Classificação COBRADE e identificação da operação</p>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="rat-grid-2">
          <FormSelect
            label="Classificação COBRADE"
            v-model="localData.dadosGerais.nat_cobrade_id"
            :options="cobradeOptions"
            placeholder="Selecione a classificação..."
          />
          <FormField
            label="Nome da Operação (Opcional)"
            v-model="localData.dadosGerais.nat_nome_operacao"
            placeholder="Ex: Operação Chuvas de Verão"
          />
        </div>
      </div>
    </div>

    <!-- Seção: Configurações do RAT -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-purple">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Configurações do RAT</h3>
          <p class="text-xs text-slate-500 mt-0.5">Configurações gerais e unidade responsável</p>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="space-y-5">
          <!-- Toggle Vistoria -->
          <div class="flex items-center justify-between p-4 rounded-lg bg-slate-950/50 border border-slate-700/50">
            <div class="flex gap-3 items-start">
              <div class="bg-purple-500/20 p-2 rounded-lg flex-shrink-0">
                <ClipboardIcon class="w-5 h-5 text-purple-400" />
              </div>
              <div>
                <p class="text-sm font-medium text-slate-200">
                  Realizou Vistoria Imobiliária?
                </p>
                <p class="text-xs text-slate-500 mt-0.5">
                  Habilita a aba de vistoria técnica
                </p>
              </div>
            </div>
            <button
              @click="toggleVistoria"
              :class="[
                'w-12 h-6 flex items-center rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-blue-500 flex-shrink-0 ml-4',
                localData.dadosGerais.tem_vistoria ? 'bg-blue-600' : 'bg-slate-700',
              ]"
            >
              <span
                :class="[
                  'w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200',
                  localData.dadosGerais.tem_vistoria ? 'translate-x-6' : 'translate-x-0.5',
                ]"
              ></span>
            </button>
          </div>

          <!-- Unidade Responsável (Readonly) -->
          <FormField
            label="Unidade Responsável"
            :value="'COMPDEC - Município Modelo/MG'"
            readonly
          />
        </div>
      </div>
    </div>

    <!-- Seção: Local do Fato -->
    <RatLocationSection
      v-model="localData.local"
    />

    <!-- Seção: Endereço Detalhado -->
    <RatAddressSection
      v-model="localData.endereco"
    />

    <!-- Footer Actions - Sticky dentro do container -->
    <div class="rat-actions-footer">
      <div class="max-w-full mx-auto flex items-center justify-center gap-3 px-4 py-4 sm:px-6">
        <button
          @click="$emit('cancel')"
          class="px-5 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-all duration-200 border border-transparent hover:border-slate-700"
        >
          Cancelar
        </button>
        <button
          @click="$emit('save-draft', localData)"
          class="px-5 py-2.5 rounded-lg text-sm font-medium bg-slate-800 text-amber-400 border border-slate-700 hover:bg-slate-700 hover:border-slate-600 transition-all duration-200"
        >
          Salvar Rascunho
        </button>
        <button
          @click="$emit('save', localData)"
          class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-2"
        >
          <CheckCircleIcon class="w-4 h-4" />
          Finalizar RAT
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import ClipboardIcon from '../Icons/ClipboardIcon.vue';
import CheckCircleIcon from '../Icons/CheckCircleIcon.vue';
import FormField from '../Form/FormField.vue';
import FormSelect from '../Form/FormSelect.vue';
import RatCommunicationSection from './Sections/RatCommunicationSection.vue';
import RatLocationSection from './Sections/RatLocationSection.vue';
import RatAddressSection from './Sections/RatAddressSection.vue';

const props = defineProps({
  rat: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(['save', 'save-draft', 'cancel', 'update:tem-vistoria']);

const localData = ref({
  dadosGerais: {
    data_fato: props.rat.dadosGerais?.data_fato || '',
    data_inicio_atividade: props.rat.dadosGerais?.data_inicio_atividade || '',
    data_termino_atividade: props.rat.dadosGerais?.data_termino_atividade || '',
    nat_cobrade_id: props.rat.dadosGerais?.nat_cobrade_id || '',
    nat_nome_operacao: props.rat.dadosGerais?.nat_nome_operacao || '',
    tem_vistoria: props.rat.tem_vistoria || false,
  },
  comunicacao: {
    data_comunicacao: props.rat.comunicacao?.data_comunicacao || '',
    tipo_solicitacao: props.rat.comunicacao?.tipo_solicitacao || '',
    telefone_contato: props.rat.comunicacao?.telefone_contato || '',
    nome_solicitante: props.rat.comunicacao?.nome_solicitante || '',
  },
  local: {
    pais_id: props.rat.local?.pais_id || '1',
    uf: props.rat.local?.uf || '',
    municipio_id: props.rat.local?.municipio_id || '',
  },
  endereco: {
    cep: props.rat.endereco?.cep || '',
    logradouro: props.rat.endereco?.logradouro || '',
    numero: props.rat.endereco?.numero || '',
    complemento: props.rat.endereco?.complemento || '',
    bairro: props.rat.endereco?.bairro || '',
    km: props.rat.endereco?.km || '',
    cruzamento: props.rat.endereco?.cruzamento || '',
    ponto_referencia: props.rat.endereco?.ponto_referencia || '',
    tipo_localizacao: props.rat.endereco?.tipo_localizacao || '',
    latitude: props.rat.endereco?.latitude || null,
    longitude: props.rat.endereco?.longitude || null,
  },
});

const cobradeOptions = [
  { value: '1', label: '1.3.2.1.0 - Tempestade Local' },
  { value: '2', label: '1.2.1.0.0 - Inundação' },
  { value: '3', label: '1.1.3.3.1 - Deslizamento de Planície' },
];

function toggleVistoria() {
  localData.value.dadosGerais.tem_vistoria = !localData.value.dadosGerais.tem_vistoria;
  emit('update:tem-vistoria', localData.value.dadosGerais.tem_vistoria);
}

watch(
  () => props.rat,
  (newVal) => {
    if (newVal && newVal.dadosGerais) {
      localData.value.dadosGerais = {
        ...localData.value.dadosGerais,
        ...newVal.dadosGerais,
      };
    }
  },
  { deep: true }
);
</script>

<style scoped>
.rat-actions-footer {
  position: sticky;
  bottom: 0;
  width: 100%;
  background: rgba(15, 23, 42, 0.95);
  backdrop-filter: blur(8px);
  border-top: 1px solid rgba(51, 65, 85, 0.5);
  z-index: 30;
  box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 -2px 4px -1px rgba(0, 0, 0, 0.06);
  margin-top: 2rem;
  /* Remove margins negativos - mantém dentro do container */
  padding-top: 1rem;
  padding-bottom: 1rem;
}

/* Espaçamento mínimo para não sobrepor conteúdo */
.rat-form-content {
  padding-bottom: 1rem;
}
</style>

