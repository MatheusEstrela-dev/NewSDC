<template>
  <div class="rat-section-card">
    <!-- Header -->
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Dados do Recurso</h3>
        <p class="text-xs text-slate-500 mt-0.5">Informações sobre viaturas, equipamentos e pessoal empregado</p>
      </div>
    </div>

    <!-- Form Grid -->
    <div class="rat-section-content">
      <!-- Linha 1: Tipo e Categoria -->
      <div class="rat-grid-2">
        <FormSelect
          label="Tipo de Recurso"
          v-model="localData.tipo_recurso"
          :options="tipoRecursoOptions"
          placeholder="Selecione o tipo..."
          required
        />
        <FormSelect
          label="Categoria"
          v-model="localData.categoria"
          :options="categoriaOptions"
          placeholder="Selecione a categoria..."
          required
        />
      </div>

      <!-- Linha 2: Órgão e Identificação -->
      <div class="rat-grid-2">
        <FormSelect
          label="Órgão Responsável"
          v-model="localData.orgao_responsavel"
          :options="orgaoOptions"
          placeholder="Selecione o órgão..."
          required
        />
        <FormField
          label="Identificação/Placa/Matrícula"
          v-model="localData.identificacao"
          placeholder="Ex: ABC-1234 ou Matrícula 12345"
          required
        />
      </div>

      <!-- Linha 3: Condutor e Descrição -->
      <div class="rat-grid-2">
        <FormField
          label="Condutor do Veículo"
          v-model="localData.condutor"
          placeholder="Nome do condutor"
        />
        <FormField
          label="Descrição do Recurso"
          v-model="localData.descricao"
          placeholder="Descrição complementar"
        />
      </div>

      <!-- Dados de Deslocamento -->
      <div class="mt-6 pt-6 border-t border-slate-700/30">
        <h4 class="text-sm font-medium text-slate-300 mb-4">Dados de Deslocamento</h4>
        <div class="rat-grid-3">
          <FormField
            label="Data/Hora de Saída"
            type="datetime-local"
            v-model="localData.data_saida"
          />
          <FormField
            label="Data/Hora de Chegada"
            type="datetime-local"
            v-model="localData.data_chegada"
          />
          <FormField
            label="KM Percorrido"
            type="number"
            v-model="localData.km_percorrido"
            placeholder="0"
          />
        </div>
        <div class="rat-grid-2 mt-4">
          <FormField
            label="Local de Origem"
            v-model="localData.local_origem"
            placeholder="Local de partida"
          />
          <FormField
            label="Local de Destino"
            v-model="localData.local_destino"
            placeholder="Local de chegada"
          />
        </div>
      </div>

      <!-- Dados Operacionais -->
      <div class="mt-6 pt-6 border-t border-slate-700/30">
        <h4 class="text-sm font-medium text-slate-300 mb-4">Dados Operacionais</h4>
        <div class="rat-grid-3">
          <FormField
            label="Quantidade"
            type="number"
            v-model="localData.quantidade"
            placeholder="1"
          />
          <FormField
            label="Capacidade/Potência"
            v-model="localData.capacidade"
            placeholder="Ex: 5000L, 150HP"
          />
          <FormSelect
            label="Condição do Recurso"
            v-model="localData.condicao"
            :options="condicaoOptions"
          />
        </div>
        <div class="rat-grid-2 mt-4">
          <FormField
            label="Operador/Responsável"
            v-model="localData.operador"
            placeholder="Nome do responsável"
          />
          <FormField
            label="Contato de Emergência"
            v-model="localData.contato_emergencia"
            placeholder="(00) 00000-0000"
            mask="phone"
          />
        </div>
        <FormField
          label="Observações"
          v-model="localData.observacoes"
          type="textarea"
          placeholder="Informações adicionais sobre o recurso"
          class="mt-4"
        />
      </div>

      <!-- Agentes/Integrantes da Guarnição -->
      <div class="mt-6 pt-6 border-t border-slate-700/30">
        <div class="flex items-center justify-between mb-4">
          <h4 class="text-sm font-medium text-slate-300">Agentes / Integrantes da Guarnição</h4>
          <button
            @click="toggleFormularioAgente"
            type="button"
            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-500/10 text-blue-400 border border-blue-500/30 hover:bg-blue-500/20 transition-all"
          >
            {{ mostrarFormularioAgente ? 'Cancelar' : '+ Adicionar Agente' }}
          </button>
        </div>

        <!-- Formulário Inline -->
        <div v-if="mostrarFormularioAgente" class="mb-6 p-6 rounded-lg bg-slate-950/50 border-2 border-blue-500/30">
          <h5 class="text-base font-semibold text-blue-400 mb-4">
            {{ agenteEditIndex !== null ? 'Editar Agente' : 'Novo Agente' }}
          </h5>

          <div class="space-y-4">
            <div class="rat-grid-2">
              <FormField
                label="Nome Completo"
                v-model="novoAgente.nome"
                required
              />
              <FormField
                label="Matrícula / MASP"
                v-model="novoAgente.matricula"
                required
              />
            </div>
            <div class="rat-grid-2">
              <FormField
                label="PG/Cargo"
                v-model="novoAgente.cargo"
              />
              <FormSelect
                label="Função no Atendimento"
                v-model="novoAgente.funcao"
                :options="funcaoOptions"
              />
            </div>
            <div class="rat-grid-2">
              <FormSelect
                label="Órgão"
                v-model="novoAgente.orgao"
                :options="orgaoOptions"
              />
              <FormField
                label="Unidade"
                v-model="novoAgente.unidade"
              />
            </div>
            <div class="flex items-center gap-3 p-4 rounded-lg bg-slate-950/50 border border-slate-700/30">
              <input
                type="checkbox"
                id="condutor-check"
                v-model="novoAgente.condutor"
                class="w-4 h-4 rounded bg-slate-800 border-slate-600 text-blue-500 focus:ring-2 focus:ring-blue-500"
              />
              <label for="condutor-check" class="text-sm text-slate-300 cursor-pointer">
                Este agente é o condutor do veículo
              </label>
            </div>
          </div>

          <div class="flex gap-3 mt-6">
            <button
              @click="cancelarFormularioAgente"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-all border border-slate-700"
            >
              Cancelar
            </button>
            <button
              @click="salvarAgente"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-500 transition-all"
            >
              {{ agenteEditIndex !== null ? 'Atualizar' : 'Adicionar' }}
            </button>
          </div>
        </div>

        <!-- Lista de Agentes -->
        <div v-if="localData.agentes && localData.agentes.length > 0" class="space-y-3">
          <div
            v-for="(agente, index) in localData.agentes"
            :key="index"
            class="p-4 rounded-lg bg-slate-950/50 border border-slate-700/30 flex items-start justify-between gap-4"
          >
            <div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-3 text-sm">
              <div>
                <span class="text-slate-500">Nome:</span>
                <span class="text-slate-200 ml-2">{{ agente.nome }}</span>
              </div>
              <div>
                <span class="text-slate-500">Matrícula:</span>
                <span class="text-slate-200 ml-2">{{ agente.matricula }}</span>
              </div>
              <div>
                <span class="text-slate-500">PG/Cargo:</span>
                <span class="text-slate-200 ml-2">{{ agente.cargo }}</span>
              </div>
              <div>
                <span class="text-slate-500">Função:</span>
                <span class="text-slate-200 ml-2">{{ agente.funcao }}</span>
              </div>
              <div>
                <span class="text-slate-500">Órgão:</span>
                <span class="text-slate-200 ml-2">{{ agente.orgao }}</span>
              </div>
              <div>
                <span class="text-slate-500">Condutor:</span>
                <span :class="agente.condutor ? 'text-emerald-400' : 'text-slate-500'" class="ml-2">
                  {{ agente.condutor ? 'Sim' : 'Não' }}
                </span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="editarAgente(index)"
                type="button"
                class="text-blue-400 hover:text-blue-300 transition-colors flex-shrink-0"
                title="Editar agente"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="removerAgente(index)"
                type="button"
                class="text-red-400 hover:text-red-300 transition-colors flex-shrink-0"
                title="Remover agente"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-8 text-slate-500 text-sm">
          Nenhum agente adicionado. Clique em "Adicionar Agente" para incluir integrantes da guarnição.
        </div>
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
  tipo_recurso: props.modelValue?.tipo_recurso || '',
  categoria: props.modelValue?.categoria || '',
  orgao_responsavel: props.modelValue?.orgao_responsavel || '',
  identificacao: props.modelValue?.identificacao || '',
  condutor: props.modelValue?.condutor || '',
  descricao: props.modelValue?.descricao || '',
  data_saida: props.modelValue?.data_saida || '',
  data_chegada: props.modelValue?.data_chegada || '',
  km_percorrido: props.modelValue?.km_percorrido || '',
  local_origem: props.modelValue?.local_origem || '',
  local_destino: props.modelValue?.local_destino || '',
  quantidade: props.modelValue?.quantidade || '1',
  capacidade: props.modelValue?.capacidade || '',
  condicao: props.modelValue?.condicao || 'operacional',
  operador: props.modelValue?.operador || '',
  contato_emergencia: props.modelValue?.contato_emergencia || '',
  observacoes: props.modelValue?.observacoes || '',
  agentes: props.modelValue?.agentes || [],
});

const mostrarFormularioAgente = ref(false);
const agenteEditIndex = ref(null);
const novoAgente = ref({
  nome: '',
  matricula: '',
  cargo: '',
  funcao: '',
  orgao: '',
  unidade: '',
  condutor: false,
});

// Options
const tipoRecursoOptions = [
  { value: 'viatura', label: 'Viatura' },
  { value: 'aeronave', label: 'Aeronave' },
  { value: 'embarcacao', label: 'Embarcação' },
  { value: 'equipamento', label: 'Equipamento' },
  { value: 'material', label: 'Material' },
  { value: 'pessoal', label: 'Pessoal' },
];

const categoriaOptions = [
  { value: 'operacional', label: 'Operacional' },
  { value: 'apoio', label: 'Apoio' },
  { value: 'logistica', label: 'Logística' },
  { value: 'saude', label: 'Saúde' },
];

const orgaoOptions = [
  { value: 'cbmmg', label: 'Corpo de Bombeiros Militar de Minas Gerais' },
  { value: 'pmmg', label: 'Polícia Militar de Minas Gerais' },
  { value: 'defesa_civil', label: 'Defesa Civil' },
  { value: 'samu', label: 'SAMU' },
  { value: 'outros', label: 'Outros' },
];

const condicaoOptions = [
  { value: 'operacional', label: 'Operacional' },
  { value: 'manutencao', label: 'Em Manutenção' },
  { value: 'inoperante', label: 'Inoperante' },
];

const funcaoOptions = [
  { value: 'comandante', label: 'Comandante' },
  { value: 'condutor', label: 'Condutor' },
  { value: 'operador', label: 'Operador' },
  { value: 'socorrista', label: 'Socorrista' },
  { value: 'auxiliar', label: 'Auxiliar' },
];

const resetFormAgente = () => {
  novoAgente.value = {
    nome: '',
    matricula: '',
    cargo: '',
    funcao: '',
    orgao: '',
    unidade: '',
    condutor: false,
  };
};

const toggleFormularioAgente = () => {
  if (mostrarFormularioAgente.value) {
    cancelarFormularioAgente();
  } else {
    resetFormAgente();
    agenteEditIndex.value = null;
    mostrarFormularioAgente.value = true;
  }
};

const cancelarFormularioAgente = () => {
  mostrarFormularioAgente.value = false;
  resetFormAgente();
  agenteEditIndex.value = null;
};

const editarAgente = (index) => {
  novoAgente.value = { ...localData.value.agentes[index] };
  agenteEditIndex.value = index;
  mostrarFormularioAgente.value = true;
  // Scroll suave para o formulário
  setTimeout(() => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }, 100);
};

const salvarAgente = () => {
  if (!novoAgente.value.nome || !novoAgente.value.matricula) {
    alert('Nome e Matrícula são obrigatórios');
    return;
  }

  if (agenteEditIndex.value !== null) {
    // Editando agente existente
    localData.value.agentes[agenteEditIndex.value] = { ...novoAgente.value };
  } else {
    // Adicionando novo agente
    localData.value.agentes.push({ ...novoAgente.value });
  }

  mostrarFormularioAgente.value = false;
  resetFormAgente();
  agenteEditIndex.value = null;
  emit('update:modelValue', localData.value);
};

const removerAgente = (index) => {
  if (confirm('Deseja realmente remover este agente?')) {
    localData.value.agentes.splice(index, 1);
    emit('update:modelValue', localData.value);
  }
};

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
      const newStr = JSON.stringify({ ...localData.value, ...newValue });

      // Só atualiza se houver diferença real
      if (currentStr !== newStr) {
        localData.value = { ...localData.value, ...newValue };
      }
    }
  },
  { deep: true }
);
</script>
