<template>
  <div class="space-y-6">
    <!-- Identificação do Solicitante -->
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
          <FormField
            label="Nome Completo"
            v-model="localData.solicitante.nome"
            required
          />
          <FormField
            label="CPF"
            v-model="localData.solicitante.cpf"
            mask="cpf"
            required
          />
        </div>
        <div class="rat-grid-3">
          <FormField
            label="Telefone"
            v-model="localData.solicitante.telefone"
            mask="phone"
          />
          <FormField
            label="CEP"
            v-model="localData.solicitante.cep"
            mask="cep"
          />
          <FormField
            label="Bairro"
            v-model="localData.solicitante.bairro"
          />
        </div>
        <FormField
          label="Endereço"
          v-model="localData.solicitante.endereco"
          required
        />
      </div>
    </div>

    <!-- Localização do Imóvel -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Localização do Imóvel</h3>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="rat-grid-2">
          <FormField
            label="Endereço do Imóvel"
            v-model="localData.imovel.endereco"
          />
          <FormField
            label="Bairro"
            v-model="localData.imovel.bairro"
          />
        </div>
        <div class="rat-grid-2">
          <FormField
            label="Município"
            v-model="localData.imovel.municipio"
          />
          <FormField
            label="CEP"
            v-model="localData.imovel.cep"
            mask="cep"
          />
        </div>
      </div>
    </div>

    <!-- Tipo de Imóvel/Estrutura -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-success">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Tipo de Imóvel / Estrutura</h3>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="rat-grid-2">
          <FormSelect
            label="Tipo de Imóvel"
            v-model="localData.estrutura.tipo_imovel"
            :options="tipoImovelOptions"
            required
          />
          <FormSelect
            label="Tipo de Construção"
            v-model="localData.estrutura.tipo_construcao"
            :options="tipoConstrucaoOptions"
          />
        </div>
        <div class="rat-grid-3">
          <FormSelect
            label="Tipo de Destinação"
            v-model="localData.estrutura.tipo_destinacao"
            :options="tipoDestinacaoOptions"
          />
          <FormSelect
            label="Tipo de Edificação"
            v-model="localData.estrutura.tipo_edificacao"
            :options="tipoEdificacaoOptions"
          />
          <FormSelect
            label="Sistema Estrutural"
            v-model="localData.estrutura.sistema_estrutural"
            :options="sistemaEstruturalOptions"
          />
        </div>
        <div class="rat-grid-3">
          <FormField
            label="Número de Pavimentos"
            type="number"
            v-model="localData.estrutura.num_pavimentos"
          />
          <FormSelect
            label="Estado de Conservação"
            v-model="localData.estrutura.estado_conservacao"
            :options="estadoConservacaoOptions"
          />
          <FormSelect
            label="Regime de Ocupação"
            v-model="localData.estrutura.regime_ocupacao"
            :options="regimeOcupacaoOptions"
          />
        </div>
      </div>
    </div>

    <!-- Caracterização dos Moradores -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-purple">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Caracterização dos Moradores</h3>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="rat-grid-2">
          <FormField
            label="Proprietário / Morador"
            v-model="localData.moradores.proprietario"
          />
          <FormField
            label="Contato Telefone"
            v-model="localData.moradores.telefone"
            mask="phone"
          />
        </div>
        <div class="rat-grid-4">
          <FormField
            label="Número de Moradores"
            type="number"
            v-model="localData.moradores.num_moradores"
          />
          <div class="flex items-center gap-2 mt-6">
            <input
              type="checkbox"
              id="ha-idosos"
              v-model="localData.moradores.ha_idosos"
              class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
            />
            <label for="ha-idosos" class="text-sm text-slate-300">Há Idosos?</label>
          </div>
          <div class="flex items-center gap-2 mt-6">
            <input
              type="checkbox"
              id="ha-criancas"
              v-model="localData.moradores.ha_criancas"
              class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
            />
            <label for="ha-criancas" class="text-sm text-slate-300">Há Crianças?</label>
          </div>
          <div class="flex items-center gap-2 mt-6">
            <input
              type="checkbox"
              id="ha-pcd"
              v-model="localData.moradores.ha_pcd"
              class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500"
            />
            <label for="ha-pcd" class="text-sm text-slate-300">Há PCD?</label>
          </div>
        </div>
      </div>
    </div>

    <!-- Patologias Identificadas -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-danger">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Patologias Identificadas</h3>
          <p class="text-xs text-slate-500 mt-0.5">Marque todos os problemas identificados na vistoria</p>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="patologia in patologiasOptions"
            :key="patologia.value"
            class="flex items-center gap-3 p-3 rounded-lg bg-slate-950/30 border border-slate-700/30 hover:border-slate-600/50 transition-all"
          >
            <input
              type="checkbox"
              :id="`pat-${patologia.value}`"
              v-model="localData.patologias[patologia.value]"
              class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-red-500"
            />
            <label
              :for="`pat-${patologia.value}`"
              class="text-sm text-slate-300 cursor-pointer flex-1"
            >
              {{ patologia.label }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <!-- Observações Gerais -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Observações Gerais da Vistoria</h3>
        </div>
      </div>

      <div class="rat-section-content">
        <FormField
          type="textarea"
          v-model="localData.observacoes"
          placeholder="Descreva detalhes adicionais identificados na vistoria..."
          :rows="6"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
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

// Options
const tipoImovelOptions = [
  { value: 'residencial', label: 'Residencial' },
  { value: 'comercial', label: 'Comercial' },
  { value: 'industrial', label: 'Industrial' },
  { value: 'misto', label: 'Misto' },
  { value: 'outro', label: 'Outro' },
];

const tipoConstrucaoOptions = [
  { value: 'alvenaria', label: 'Alvenaria' },
  { value: 'madeira', label: 'Madeira' },
  { value: 'mista', label: 'Mista' },
  { value: 'concreto', label: 'Concreto' },
];

const tipoDestinacaoOptions = [
  { value: 'moradia', label: 'Moradia' },
  { value: 'comercio', label: 'Comércio' },
  { value: 'servicos', label: 'Serviços' },
  { value: 'industrial', label: 'Industrial' },
];

const tipoEdificacaoOptions = [
  { value: 'casa', label: 'Casa' },
  { value: 'sobrado', label: 'Sobrado' },
  { value: 'apartamento', label: 'Apartamento' },
  { value: 'barracao', label: 'Barracão' },
  { value: 'galpao', label: 'Galpão' },
];

const sistemaEstruturalOptions = [
  { value: 'concreto_armado', label: 'Concreto Armado' },
  { value: 'alvenaria_estrutural', label: 'Alvenaria Estrutural' },
  { value: 'metalico', label: 'Metálico' },
  { value: 'madeira', label: 'Madeira' },
];

const estadoConservacaoOptions = [
  { value: 'bom', label: 'Bom' },
  { value: 'regular', label: 'Regular' },
  { value: 'ruim', label: 'Ruim' },
  { value: 'pessimo', label: 'Péssimo' },
];

const regimeOcupacaoOptions = [
  { value: 'proprio', label: 'Próprio' },
  { value: 'alugado', label: 'Alugado' },
  { value: 'cedido', label: 'Cedido' },
  { value: 'invadido', label: 'Invadido' },
];

const patologiasOptions = [
  { value: 'trincas', label: 'Trincas' },
  { value: 'fissuras', label: 'Fissuras' },
  { value: 'rachaduras', label: 'Rachaduras' },
  { value: 'umidade', label: 'Umidade' },
  { value: 'infiltracao', label: 'Infiltração' },
  { value: 'desplacamento', label: 'Desplacamento de Revestimento' },
  { value: 'fundacoes', label: 'Comprometimento das Fundações' },
  { value: 'talude', label: 'Instabilidade de Talude/Contenções' },
  { value: 'movimentacao_solo', label: 'Indícios de Movimentação de Solo' },
  { value: 'muralhas', label: 'Risco de Tombamento de Muralhas' },
  { value: 'inundacoes', label: 'Inundações' },
  { value: 'alagamentos', label: 'Alagamentos' },
  { value: 'enxurradas', label: 'Enxurradas' },
  { value: 'madeira', label: 'Patologia em Elementos de Madeira' },
  { value: 'nao_estruturais', label: 'Patologia em Elementos Não Estruturais' },
];

// Watch para emitir mudanças do localData para o pai
watch(
  localData,
  (newValue) => {
    emit('update:modelValue', newValue);
  },
  { deep: true }
);

// Watch para sincronizar props.modelValue com localData, evitando loops infinitos
watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      // Compara valores para evitar atualizações desnecessárias
      const currentStr = JSON.stringify(localData.value);
      const newStr = JSON.stringify(newValue);

      // Só atualiza se houver diferença real
      if (currentStr !== newStr) {
        Object.assign(localData.value, newValue);
      }
    }
  },
  { deep: true }
);
</script>
