<template>
  <div class="space-y-4 sm:space-y-6 rat-form-content">
    <!-- Secao: Atendimento -->
    <RatCollapsibleSection
      section-id="atendimento"
      title="Atendimento"
      subtitle="Data e horario do fato e atividades"
      icon-class="rat-section-icon-default"
      :default-expanded="true"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
      </template>
      <div class="rat-grid-3">
        <FormField
          label="Data/Hora do Fato"
          type="datetime-local"
          v-model="localData.dadosGerais.data_fato"
          required
        />
        <FormField
          label="Inicio da Atividade"
          type="datetime-local"
          v-model="localData.dadosGerais.data_inicio_atividade"
          required
        />
        <FormField
          label="Termino da Atividade"
          type="datetime-local"
          v-model="localData.dadosGerais.data_termino_atividade"
        />
      </div>
    </RatCollapsibleSection>

    <!-- Secao: Comunicacao da Ocorrencia -->
    <RatCommunicationSection
      v-model="localData.comunicacao"
    />

    <!-- Secao: Natureza da Ocorrencia -->
    <RatCollapsibleSection
      section-id="natureza"
      title="Natureza da Ocorrencia"
      subtitle="Classificacao COBRADE e identificacao da operacao"
      icon-class="rat-section-icon-success"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </template>
      <div class="rat-grid-2">
        <FormSelect
          label="Classificacao COBRADE"
          v-model="localData.dadosGerais.nat_cobrade_id"
          :options="cobradeOptions"
          placeholder="Selecione a classificacao..."
        />
        <FormField
          label="Nome da Operacao (Opcional)"
          v-model="localData.dadosGerais.nat_nome_operacao"
          placeholder="Ex: Operacao Chuvas de Verao"
        />
      </div>
    </RatCollapsibleSection>

    <!-- Secao: Configuracoes do RAT -->
    <RatCollapsibleSection
      section-id="configuracoes"
      title="Configuracoes do RAT"
      subtitle="Configuracoes gerais e unidade responsavel"
      icon-class="rat-section-icon-purple"
    >
      <template #icon>
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </template>
      <div class="space-y-4 sm:space-y-5">
        <!-- Toggle Vistoria -->
        <div class="flex items-center justify-between p-3 sm:p-4 rounded-lg bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-700/50">
          <div class="flex gap-2 sm:gap-3 items-start">
            <div class="bg-purple-500/20 p-1.5 sm:p-2 rounded-lg flex-shrink-0">
              <ClipboardIcon class="w-4 h-4 sm:w-5 sm:h-5 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <p class="text-xs sm:text-sm font-medium text-slate-800 dark:text-slate-200">
                Realizou Vistoria Imobiliaria?
              </p>
              <p class="text-xs text-slate-500 mt-0.5 hidden sm:block">
                Habilita a aba de vistoria tecnica
              </p>
            </div>
          </div>
          <button
            @click="toggleVistoria"
            :class="[
              'w-11 h-6 sm:w-12 sm:h-6 flex items-center rounded-full transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-slate-900 focus:ring-blue-500 flex-shrink-0 ml-2 sm:ml-4',
              localData.dadosGerais.tem_vistoria ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700',
            ]"
          >
            <span
              :class="[
                'w-5 h-5 bg-white rounded-full shadow-md transform transition-transform duration-200',
                localData.dadosGerais.tem_vistoria ? 'translate-x-5 sm:translate-x-6' : 'translate-x-0.5',
              ]"
            ></span>
          </button>
        </div>

        <!-- Unidade Responsavel (Readonly) -->
        <FormField
          label="Unidade Responsavel"
          :value="'COMPDEC - Municipio Modelo/MG'"
          readonly
        />
      </div>
    </RatCollapsibleSection>

    <!-- Secao: Local do Fato -->
    <RatLocationSection
      v-model="localData.local"
    />

    <!-- Secao: Endereco Detalhado -->
    <RatAddressSection
      v-model="localData.endereco"
    />

    <!-- Footer Actions - Sticky Mobile Optimized -->
    <div class="rat-actions-footer">
      <div class="max-w-full mx-auto flex items-center justify-center gap-2 sm:gap-3 px-3 py-3 sm:px-6 sm:py-4">
        <button
          @click="$emit('cancel')"
          class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-all duration-200 border border-transparent hover:border-slate-300 dark:hover:border-slate-700"
        >
          Cancelar
        </button>
        <button
          @click="$emit('save-draft', localData)"
          class="px-3 sm:px-5 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-medium bg-white dark:bg-slate-800 text-amber-600 dark:text-amber-400 border border-slate-300 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 hover:border-slate-400 dark:hover:border-slate-600 transition-all duration-200"
        >
          <span class="hidden sm:inline">Salvar Rascunho</span>
          <span class="sm:hidden">Salvar</span>
        </button>
        <button
          @click="$emit('save', localData)"
          class="px-4 sm:px-6 py-2 sm:py-2.5 rounded-lg text-xs sm:text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 text-white hover:from-blue-500 hover:to-blue-400 shadow-lg shadow-blue-600/25 transition-all duration-200 flex items-center gap-1.5 sm:gap-2"
        >
          <CheckCircleIcon class="w-4 h-4" />
          <span class="hidden sm:inline">Finalizar RAT</span>
          <span class="sm:hidden">Finalizar</span>
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
import RatCollapsibleSection from './Sections/RatCollapsibleSection.vue';
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
  @apply bg-white/95 dark:bg-slate-900/95 border-t border-slate-200 dark:border-slate-700/50;
  backdrop-filter: blur(8px);
  z-index: 30;
  box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1), 0 -2px 4px -1px rgba(0, 0, 0, 0.06);
  margin-top: 1rem;
}

/* Mobile: footer mais compacto */
@media (max-width: 640px) {
  .rat-actions-footer {
    margin-top: 0.75rem;
  }
}

/* Espacamento minimo para nao sobrepor conteudo */
.rat-form-content {
  padding-bottom: 0.5rem;
}
</style>

