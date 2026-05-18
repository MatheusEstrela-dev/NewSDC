<template>
  <div class="space-y-6">
    <!-- Card 1: Dados do Recurso -->
    <div class="rat-section-card">
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

      <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <FormSelect label="Tipo de Recurso" v-model="localData.tipo_recurso" :options="tipoRecursoOptions" required />
            <FormSelect label="Categoria" v-model="localData.categoria" :options="categoriaOptions" required />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Órgão Responsável" v-model="localData.orgao_responsavel" :options="orgaoOptions" required />
            <FormField label="Identificação / Placa / Matrícula" v-model="localData.identificacao" required />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Condição" v-model="localData.condicao" :options="condicaoOptions" />
            <FormField label="Descrição do Recurso" v-model="localData.descricao" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Card 2: Dados de Deslocamento -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados de Deslocamento</h3>
          <p class="text-xs text-slate-500 mt-0.5">Datas, horários e localização</p>
        </div>
      </div>
      <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-2">
            <!-- Data/Hora de Saída (split) -->
            <div>
              <label class="form-label">Data/Hora de Saída</label>
              <div class="flex gap-2">
                <div class="flex-1 min-w-0">
                  <DatePicker
                    :model-value="getDate(localData.data_saida)"
                    @update:model-value="(v) => updateSaida('date', v)"
                  />
                </div>
                <TimePicker
                  :model-value="getTime(localData.data_saida)"
                  @update:model-value="(v) => updateSaida('time', v)"
                  extra-class="w-28"
                />
              </div>
            </div>
            <!-- Data/Hora de Chegada (split) -->
            <div>
              <label class="form-label">Data/Hora de Chegada</label>
              <div class="flex gap-2">
                <div class="flex-1 min-w-0">
                  <DatePicker
                    :model-value="getDate(localData.data_chegada)"
                    @update:model-value="(v) => updateChegada('date', v)"
                  />
                </div>
                <TimePicker
                  :model-value="getTime(localData.data_chegada)"
                  @update:model-value="(v) => updateChegada('time', v)"
                  extra-class="w-28"
                />
              </div>
            </div>
          </div>
          <div class="rat-grid-3">
            <FormField label="KM Percorrido" type="number" v-model="localData.km_percorrido" />
            <FormField label="Local de Origem" v-model="localData.local_origem" />
            <FormField label="Local de Destino" v-model="localData.local_destino" />
          </div>
        </div>
      </fieldset>
    </div>

    <!-- Card 3: Dados Operacionais -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados Operacionais</h3>
          <p class="text-xs text-slate-500 mt-0.5">Capacidade e condição do recurso</p>
        </div>
      </div>
      <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
        <div class="rat-section-content">
          <div class="rat-grid-3">
            <FormField label="Quantidade" type="number" v-model="localData.quantidade" />
            <FormField label="Capacidade / Potência" type="number" v-model="localData.capacidade" />
            <FormField label="Contato de Emergência" v-model="localData.contato_emergencia" mask="phone" />
          </div>
          <FormField label="Observações" v-model="localData.observacoes" type="textarea" />
        </div>
      </fieldset>
    </div>

    <!-- Card 4: Agentes/Integrantes -->
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-default">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
        </div>
        <div class="flex-1">
          <h3 class="rat-section-title">Agentes / Integrantes</h3>
          <p class="text-xs text-slate-500 mt-0.5">Guarnição envolvida</p>
        </div>
        <button
          v-if="!props.viewOnly"
          @click="toggleFormularioAgente"
          type="button"
          class="px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-500/10 text-blue-600 border border-blue-500/30 hover:bg-blue-500/20 transition-colors"
        >
          {{ mostrarFormularioAgente ? 'Cancelar' : '+ Agente' }}
        </button>
      </div>

      <div class="rat-section-content">
        <!-- Form Agente -->
        <div v-if="mostrarFormularioAgente" class="mb-6 p-6 rounded-lg bg-slate-50 dark:bg-slate-950/50 border-2 border-blue-500/30 space-y-4">
          <div class="rat-grid-3">
            <FormField label="Nome Completo" v-model="novoAgente.nome" required />
            <FormField label="Matrícula" v-model="novoAgente.matricula" />
            <FormField label="MASP" v-model="novoAgente.masp" />
          </div>
          <div class="rat-grid-3">
            <FormField label="Posto / Graduação / Cargo" v-model="novoAgente.pg_cargo" />
            <FormField label="Órgão" v-model="novoAgente.orgao" />
            <FormField label="Unidade" v-model="novoAgente.unidade" />
          </div>
          <div class="rat-grid-2">
            <FormSelect label="Função" v-model="novoAgente.funcao" :options="funcaoOptions" />
            <div>
              <label class="form-label">Condutor</label>
              <div class="flex gap-5 mt-2.5">
                <label class="radio-label">
                  <input type="radio" :value="true" v-model="novoAgente.is_condutor" class="radio-input" />
                  Sim
                </label>
                <label class="radio-label">
                  <input type="radio" :value="false" v-model="novoAgente.is_condutor" class="radio-input" />
                  Não
                </label>
              </div>
            </div>
          </div>
          <div class="flex gap-3 pt-2">
            <button @click="cancelarFormularioAgente" type="button" class="flex-1 px-4 py-2 rounded-lg text-sm border border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
              Cancelar
            </button>
            <button @click="salvarAgente" type="button" class="flex-1 px-4 py-2 rounded-lg text-sm bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
              Salvar Agente
            </button>
          </div>
        </div>

        <!-- Lista Agentes -->
        <div v-if="localData.agentes && localData.agentes.length > 0" class="space-y-2">
          <div
            v-for="(agente, index) in localData.agentes"
            :key="index"
            class="p-4 rounded-lg bg-slate-50 dark:bg-slate-950/50 border border-slate-200 dark:border-slate-700/30 flex justify-between items-center"
          >
            <div>
              <div class="font-medium text-slate-800 dark:text-slate-200">{{ agente.nome }}</div>
              <div class="text-xs text-slate-500 mt-0.5">
                {{ agente.pg_cargo || agente.cargo }}
                <template v-if="agente.matricula"> · Mat. {{ agente.matricula }}</template>
                <template v-if="agente.masp"> · MASP {{ agente.masp }}</template>
                <template v-if="agente.orgao"> · {{ agente.orgao }}</template>
                <template v-if="agente.is_condutor"> · Condutor</template>
              </div>
            </div>
            <button
              v-if="!props.viewOnly"
              @click="removerAgente(index)"
              type="button"
              class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
              </svg>
            </button>
          </div>
        </div>
        <div v-else class="text-center py-6 text-slate-500 text-sm">Nenhum agente adicionado.</div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';
import DatePicker from '../../Form/DatePicker.vue';
import TimePicker from '../../Form/TimePicker.vue';

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  viewOnly:   { type: Boolean, default: false },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref({
  tipo_recurso:      props.modelValue?.tipo_recurso      || '',
  categoria:         props.modelValue?.categoria         || '',
  orgao_responsavel: props.modelValue?.orgao_responsavel || '',
  identificacao:     props.modelValue?.identificacao     || '',
  descricao:         props.modelValue?.descricao         || '',
  data_saida:        props.modelValue?.data_saida        || '',
  data_chegada:      props.modelValue?.data_chegada      || '',
  km_percorrido:     props.modelValue?.km_percorrido     || '',
  local_origem:      props.modelValue?.local_origem      || '',
  local_destino:     props.modelValue?.local_destino     || '',
  quantidade:        props.modelValue?.quantidade        || '',
  capacidade:        props.modelValue?.capacidade        || '',
  condicao:          props.modelValue?.condicao          || '',
  contato_emergencia:props.modelValue?.contato_emergencia|| '',
  observacoes:       props.modelValue?.observacoes       || '',
  agentes:           props.modelValue?.agentes           || [],
});

// Date/time helpers
function getDate(datetime) {
  if (!datetime) return '';
  if (datetime.includes('T')) return datetime.split('T')[0];
  if (datetime.includes(' ')) return datetime.split(' ')[0];
  return datetime;
}

function getTime(datetime) {
  if (!datetime) return '';
  if (datetime.includes('T')) return (datetime.split('T')[1] || '').substring(0, 5);
  if (datetime.includes(' ')) return (datetime.split(' ')[1] || '').substring(0, 5);
  return '';
}

function combine(date, time) {
  if (!date) return '';
  return `${date}T${time || '00:00'}`;
}

function updateSaida(part, value) {
  const current = localData.value.data_saida || '';
  const date = part === 'date' ? value : getDate(current);
  const time = part === 'time' ? value : (getTime(current) || '00:00');
  localData.value = { ...localData.value, data_saida: combine(date, time) };
}

function updateChegada(part, value) {
  const current = localData.value.data_chegada || '';
  const date = part === 'date' ? value : getDate(current);
  const time = part === 'time' ? value : (getTime(current) || '00:00');
  localData.value = { ...localData.value, data_chegada: combine(date, time) };
}

// Agente form
const mostrarFormularioAgente = ref(false);
const novoAgente = ref({ nome: '', matricula: '', masp: '', pg_cargo: '', orgao: '', unidade: '', funcao: '', is_condutor: false });

function toggleFormularioAgente() { mostrarFormularioAgente.value = !mostrarFormularioAgente.value; }
function cancelarFormularioAgente() { mostrarFormularioAgente.value = false; }

function salvarAgente() {
  if (!novoAgente.value.nome) return alert('Nome do agente é obrigatório');
  localData.value.agentes.push({ ...novoAgente.value });
  novoAgente.value = { nome: '', matricula: '', masp: '', pg_cargo: '', orgao: '', unidade: '', funcao: '', is_condutor: false };
  mostrarFormularioAgente.value = false;
  emit('update:modelValue', localData.value);
}

function removerAgente(index) {
  localData.value.agentes.splice(index, 1);
  emit('update:modelValue', localData.value);
}

// Options
const tipoRecursoOptions = [
  { value: 'viatura',    label: 'Viatura' },
  { value: 'aeronave',   label: 'Aeronave' },
  { value: 'embarcacao', label: 'Embarcação' },
  { value: 'equipamento',label: 'Equipamento' },
  { value: 'material',   label: 'Material' },
  { value: 'pessoal',    label: 'Pessoal' },
];

const categoriaOptions = [
  { value: 'operacional', label: 'Operacional' },
  { value: 'apoio',       label: 'Apoio' },
  { value: 'logistica',   label: 'Logística' },
  { value: 'saude',       label: 'Saúde' },
];

const orgaoOptions = [
  { value: 'cbmmg',       label: 'Corpo de Bombeiros Militar de MG' },
  { value: 'pmmg',        label: 'Polícia Militar de MG' },
  { value: 'defesa_civil',label: 'Defesa Civil' },
  { value: 'samu',        label: 'SAMU' },
  { value: 'outros',      label: 'Outros' },
];

const condicaoOptions = [
  { value: 'otima',       label: 'Ótima' },
  { value: 'boa',         label: 'Boa' },
  { value: 'regular',     label: 'Regular' },
  { value: 'ruim',        label: 'Ruim' },
  { value: 'inoperante',  label: 'Inoperante' },
];

const funcaoOptions = [
  { value: 'comandante', label: 'Comandante' },
  { value: 'condutor',   label: 'Condutor' },
  { value: 'operador',   label: 'Operador' },
  { value: 'socorrista', label: 'Socorrista' },
  { value: 'auxiliar',   label: 'Auxiliar' },
];

// Sync local → parent
watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

// Sync parent → local
watch(
  () => props.modelValue,
  (nv) => {
    if (nv && JSON.stringify(nv) !== JSON.stringify(localData.value)) {
      localData.value = JSON.parse(JSON.stringify(nv));
    }
  },
  { deep: true }
);
</script>

<style scoped>
.form-label {
  @apply block text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5 sm:mb-2;
}

.radio-label {
  @apply flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none;
}

.radio-input {
  @apply h-4 w-4 text-blue-600 border-slate-300 dark:border-slate-600 focus:ring-blue-500 cursor-pointer;
}

.dt-input {
  @apply px-3 py-2.5 text-sm rounded-xl bg-white dark:bg-slate-900/50
    text-slate-900 dark:text-slate-200
    placeholder-slate-400 dark:placeholder-slate-500
    border border-slate-300 dark:border-slate-700/50
    hover:border-slate-400 dark:hover:border-slate-600
    focus:outline-none focus:ring-2 focus:ring-offset-0 focus:border-emerald-500 focus:ring-emerald-500/20
    transition-all duration-200;
}

.dt-input-filled {
  @apply border-2 border-emerald-500/60 hover:border-emerald-500/80;
}
</style>
