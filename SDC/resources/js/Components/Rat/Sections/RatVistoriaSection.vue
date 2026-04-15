<template>
  <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
    <div class="space-y-6">
      <!-- Card 1: Identificação do Solicitante -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-warning">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div>
            <h3 class="rat-section-title">Identificação do Solicitante</h3>
          </div>
        </div>

        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormField label="Nome Completo" v-model="localData.solicitante.nome" required />
            <FormField label="CPF" v-model="localData.solicitante.cpf" mask="cpf" required />
          </div>
          <div class="rat-grid-3">
            <FormField label="Telefone" v-model="localData.solicitante.telefone" mask="phone" />
            <FormField label="CEP" v-model="localData.solicitante.cep" mask="cep" />
            <FormField label="Bairro" v-model="localData.solicitante.bairro" />
          </div>
          <FormField label="Endereço Completo" v-model="localData.solicitante.endereco" required />
        </div>
      </div>

      <!-- Card 2: Localização do Imóvel -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            </svg>
          </div>
          <div>
            <h3 class="rat-section-title">Localização do Imóvel</h3>
          </div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormField label="Endereço do Imóvel" v-model="localData.imovel.endereco" />
            <FormField label="Bairro" v-model="localData.imovel.bairro" />
          </div>
          <div class="rat-grid-2">
            <FormField label="Município" v-model="localData.imovel.municipio" />
            <FormField label="CEP" v-model="localData.imovel.cep" mask="cep" />
          </div>
        </div>
      </div>

      <!-- Card 3: Tipo de Imóvel -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-success">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
          </div>
          <div>
            <h3 class="rat-section-title">Estrutura do Imóvel</h3>
          </div>
        </div>
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Imóvel" v-model="localData.estrutura.tipo_imovel" :options="tipoImovelOptions" required />
            <FormSelect label="Construção" v-model="localData.estrutura.tipo_construcao" :options="tipoConstrucaoOptions" />
          </div>
          <div class="rat-grid-3">
            <FormField label="Pavimentos" type="number" v-model="localData.estrutura.num_pavimentos" />
            <FormSelect label="Conservação" v-model="localData.estrutura.estado_conservacao" :options="estadoConservacaoOptions" />
            <FormSelect label="Ocupação" v-model="localData.estrutura.regime_ocupacao" :options="regimeOcupacaoOptions" />
          </div>
        </div>
      </div>

      <!-- Card 4: Patologias -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-danger">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
          </div>
          <div class="flex-1">
            <h3 class="rat-section-title">Patologias Identificadas</h3>
          </div>
        </div>
        <div class="rat-section-content">
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(val, key) in localData.patologias" :key="key" class="flex items-center gap-3 p-3 rounded-lg bg-slate-950/30 border border-slate-700/30">
              <input type="checkbox" :id="`pat-${key}`" v-model="localData.patologias[key]" class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-red-500" />
              <label :for="`pat-${key}`" class="text-xs text-slate-300 capitalize cursor-pointer">{{ key.replace('_', ' ') }}</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Observações -->
      <div class="rat-section-card">
        <div class="rat-section-header">
          <div class="rat-section-icon rat-section-icon-default">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/></svg>
          </div>
          <div><h3 class="rat-section-title">Observações da Vistoria</h3></div>
        </div>
        <div class="rat-section-content">
          <FormField type="textarea" v-model="localData.observacoes" :rows="4" />
        </div>
      </div>
    </div>
  </fieldset>
</template>

<script setup>
import { getCurrentInstance, ref, watch } from 'vue';
const uid = getCurrentInstance().uid;
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  viewOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({
  solicitante: {
    nome: props.modelValue?.solicitante?.nome || '',
    cpf: props.modelValue?.solicitante?.cpf || '',
    telefone: props.modelValue?.solicitante?.telefone || '',
    endereco: props.modelValue?.solicitante?.endereco || '',
    bairro: props.modelValue?.solicitante?.bairro || '',
    cep: props.modelValue?.solicitante?.cep || '',
  },
  imovel: {
    endereco: props.modelValue?.imovel?.endereco || '',
    bairro: props.modelValue?.imovel?.bairro || '',
    municipio: props.modelValue?.imovel?.municipio || '',
    cep: props.modelValue?.imovel?.cep || '',
  },
  estrutura: {
    tipo_imovel: props.modelValue?.estrutura?.tipo_imovel || '',
    tipo_construcao: props.modelValue?.estrutura?.tipo_construcao || '',
    tipo_destinacao: props.modelValue?.estrutura?.tipo_destinacao || '',
    tipo_edificacao: props.modelValue?.estrutura?.tipo_edificacao || '',
    sistema_estrutural: props.modelValue?.estrutura?.sistema_estrutural || '',
    num_pavimentos: props.modelValue?.estrutura?.num_pavimentos || '1',
    estado_conservacao: props.modelValue?.estrutura?.estado_conservacao || '',
    regime_ocupacao: props.modelValue?.estrutura?.regime_ocupacao || '',
  },
  moradores: {
    proprietario: props.modelValue?.moradores?.proprietario || '',
    telefone: props.modelValue?.moradores?.telefone || '',
    num_moradores: props.modelValue?.moradores?.num_moradores || '',
    ha_idosos: props.modelValue?.moradores?.ha_idosos || false,
    ha_criancas: props.modelValue?.moradores?.ha_criancas || false,
    ha_pcd: props.modelValue?.moradores?.ha_pcd || false,
  },
  patologias: {
    trincas: props.modelValue?.patologias?.trincas || false,
    fissuras: props.modelValue?.patologias?.fissuras || false,
    rachaduras: props.modelValue?.patologias?.rachaduras || false,
    umidade: props.modelValue?.patologias?.umidade || false,
    infiltracao: props.modelValue?.patologias?.infiltracao || false,
    desplacamento: props.modelValue?.patologias?.desplacamento || false,
    fundacoes: props.modelValue?.patologias?.fundacoes || false,
    talude: props.modelValue?.patologias?.talude || false,
    movimentacao_solo: props.modelValue?.patologias?.movimentacao_solo || false,
    muralhas: props.modelValue?.patologias?.muralhas || false,
    inundacoes: props.modelValue?.patologias?.inundacoes || false,
    alagamentos: props.modelValue?.patologias?.alagamentos || false,
    enxurradas: props.modelValue?.patologias?.enxurradas || false,
    madeira: props.modelValue?.patologias?.madeira || false,
    nao_estruturais: props.modelValue?.patologias?.nao_estruturais || false,
  },
  observacoes: props.modelValue?.observacoes || '',
});

const tipoImovelOptions = [ { value: 'residencial', label: 'Residencial' }, { value: 'comercial', label: 'Comercial' }, { value: 'outro', label: 'Outro' } ];
const tipoConstrucaoOptions = [ { value: 'alvenaria', label: 'Alvenaria' }, { value: 'madeira', label: 'Madeira' }, { value: 'concreto', label: 'Concreto' } ];
const estadoConservacaoOptions = [ { value: 'bom', label: 'Bom' }, { value: 'regular', label: 'Regular' }, { value: 'ruim', label: 'Ruim' } ];
const regimeOcupacaoOptions = [ { value: 'proprio', label: 'Próprio' }, { value: 'alugado', label: 'Alugado' } ];

watch(localData, (nv) => { emit('update:modelValue', nv); }, { deep: true });
watch(() => props.modelValue, (nv) => { if (nv) localData.value = { ...localData.value, ...nv }; }, { deep: true });
</script>
