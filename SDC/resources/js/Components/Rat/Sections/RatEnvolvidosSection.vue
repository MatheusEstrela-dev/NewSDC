<template>
  <div class="rat-section-card">
    <!-- Header -->
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-purple">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
      </div>
      <div class="flex-1">
        <h3 class="rat-section-title">Pessoas Envolvidas</h3>
        <p class="text-xs text-slate-500 mt-0.5">Registro de vítimas, testemunhas e outras pessoas relacionadas à ocorrência</p>
      </div>
    </div>

    <div class="rat-section-content">
      <!-- Formulário Inline - Sempre visível por padrão -->
      <div class="mb-6 p-6 rounded-lg bg-slate-950/50 border-2 border-purple-500/30">
        <h4 class="text-base font-semibold text-purple-400 mb-4">
          {{ envolvidoEditIndex !== null ? 'Editar Envolvido' : 'Novo Envolvido' }}
        </h4>

        <div class="space-y-6">
          <!-- Dados Pessoais -->
          <div>
            <h5 class="text-sm font-medium text-slate-300 mb-4 pb-2 border-b border-slate-700/30">Dados Pessoais</h5>
            <div class="space-y-4">
              <div class="rat-grid-2">
                <FormSelect
                  label="Tipo de Pessoa"
                  v-model="formEnvolvido.tipo_pessoa"
                  :options="tipoPessoaOptions"
                  required
                />
                <FormField
                  label="CPF"
                  v-model="formEnvolvido.cpf"
                  mask="cpf"
                  required
                />
              </div>
              <div class="rat-grid-2">
                <FormField
                  label="Nome Completo / Razão Social"
                  v-model="formEnvolvido.nome"
                  required
                />
                <FormField
                  label="Nome Social"
                  v-model="formEnvolvido.nome_social"
                />
              </div>
              <div class="rat-grid-3">
                <FormField
                  label="Data de Nascimento"
                  type="date"
                  v-model="formEnvolvido.data_nascimento"
                />
                <FormField
                  label="Idade Aparente"
                  type="number"
                  v-model="formEnvolvido.idade_aparente"
                />
                <FormSelect
                  label="Sexo"
                  v-model="formEnvolvido.sexo"
                  :options="sexoOptions"
                />
              </div>
              <div class="rat-grid-2">
                <FormField
                  label="Nome da Mãe"
                  v-model="formEnvolvido.nome_mae"
                />
                <FormField
                  label="Nome do Pai"
                  v-model="formEnvolvido.nome_pai"
                />
              </div>
              <div class="rat-grid-2">
                <FormField
                  label="Ocupação Atual"
                  v-model="formEnvolvido.ocupacao"
                />
                <FormSelect
                  label="Escolaridade"
                  v-model="formEnvolvido.escolaridade"
                  :options="escolaridadeOptions"
                />
              </div>
            </div>
          </div>

          <!-- Endereço -->
          <div>
            <h5 class="text-sm font-medium text-slate-300 mb-4 pb-2 border-b border-slate-700/30">Endereço</h5>
            <div class="space-y-4">
              <div class="rat-grid-3">
                <FormField
                  label="CEP"
                  v-model="formEnvolvido.cep"
                  mask="cep"
                  @blur="buscarCepEnvolvido"
                />
                <FormField
                  label="UF"
                  v-model="formEnvolvido.uf"
                  maxlength="2"
                />
                <FormField
                  label="Município"
                  v-model="formEnvolvido.municipio"
                />
              </div>
              <div class="rat-grid-2">
                <FormField
                  label="Logradouro"
                  v-model="formEnvolvido.logradouro"
                />
                <FormField
                  label="Bairro"
                  v-model="formEnvolvido.bairro"
                />
              </div>
              <div class="rat-grid-3">
                <FormField
                  label="Número"
                  v-model="formEnvolvido.numero"
                />
                <FormField
                  label="Complemento"
                  v-model="formEnvolvido.complemento"
                />
              </div>
            </div>
          </div>

          <!-- Contato -->
          <div>
            <h5 class="text-sm font-medium text-slate-300 mb-4 pb-2 border-b border-slate-700/30">Contato</h5>
            <div class="rat-grid-2">
              <FormField
                label="Telefone"
                v-model="formEnvolvido.telefone"
                mask="phone"
              />
              <FormField
                label="E-mail"
                type="email"
                v-model="formEnvolvido.email"
              />
            </div>
          </div>

          <!-- Ações -->
          <div class="flex gap-3 pt-4 border-t border-slate-700/30">
            <button
              v-if="envolvidoEditIndex !== null"
              @click="cancelarFormulario"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800 transition-all border border-slate-700"
            >
              Cancelar Edição
            </button>
            <button
              @click="salvarEnvolvido"
              type="button"
              class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium bg-purple-600 text-white hover:bg-purple-500 transition-all"
            >
              {{ envolvidoEditIndex !== null ? 'Salvar Alterações' : 'Adicionar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Lista de Envolvidos -->
      <div v-if="localData.length > 0" class="space-y-4">
        <p class="text-sm text-slate-400 mb-4">
          {{ localData.length }} pessoa(s) registrada(s)
        </p>

        <div
          v-for="(envolvido, index) in localData"
          :key="index"
          class="p-5 rounded-lg bg-slate-950/50 border border-slate-700/30 hover:border-slate-600/50 transition-all"
        >
          <div class="flex items-start justify-between gap-4">
            <div class="flex-1">
              <!-- Nome e Tipo -->
              <div class="flex items-center gap-3 mb-3">
                <h4 class="text-base font-semibold text-slate-200">{{ envolvido.nome }}</h4>
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-purple-500/20 text-purple-400">
                  {{ envolvido.tipo_pessoa }}
                </span>
              </div>

              <!-- Informações Principais -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm mb-3">
                <div v-if="envolvido.cpf">
                  <span class="text-slate-500">CPF:</span>
                  <span class="text-slate-300 ml-2">{{ envolvido.cpf }}</span>
                </div>
                <div v-if="envolvido.data_nascimento">
                  <span class="text-slate-500">Data Nasc.:</span>
                  <span class="text-slate-300 ml-2">{{ formatarData(envolvido.data_nascimento) }}</span>
                </div>
                <div v-if="envolvido.sexo">
                  <span class="text-slate-500">Sexo:</span>
                  <span class="text-slate-300 ml-2">{{ envolvido.sexo }}</span>
                </div>
                <div v-if="envolvido.telefone">
                  <span class="text-slate-500">Telefone:</span>
                  <span class="text-slate-300 ml-2">{{ envolvido.telefone }}</span>
                </div>
                <div v-if="envolvido.ocupacao">
                  <span class="text-slate-500">Ocupação:</span>
                  <span class="text-slate-300 ml-2">{{ envolvido.ocupacao }}</span>
                </div>
              </div>

              <!-- Endereço -->
              <div v-if="envolvido.logradouro" class="text-xs text-slate-500">
                <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ envolvido.logradouro }}{{ envolvido.numero ? ', ' + envolvido.numero : '' }}
                {{ envolvido.bairro ? ' - ' + envolvido.bairro : '' }}
                {{ envolvido.municipio ? ' - ' + envolvido.municipio : '' }}
              </div>
            </div>

            <!-- Ações -->
            <div class="flex gap-2">
              <button
                @click="editarEnvolvido(index)"
                type="button"
                class="p-2 rounded-lg text-blue-400 hover:bg-blue-500/10 transition-all"
                title="Editar"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
              </button>
              <button
                @click="removerEnvolvido(index)"
                type="button"
                class="p-2 rounded-lg text-red-400 hover:bg-red-500/10 transition-all"
                title="Remover"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State - Só aparece quando não há envolvidos e não está editando -->
      <div v-if="localData.length === 0 && envolvidoEditIndex === null" class="text-center py-8 mt-4">
        <p class="text-slate-500 text-sm">Nenhuma pessoa registrada ainda. Preencha o formulário acima para adicionar.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';
import { useCep } from '../../../composables/useCep';

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['update:modelValue']);

const localData = ref([...props.modelValue]);
const mostrarFormulario = ref(true); // Sempre visível por padrão
const envolvidoEditIndex = ref(null);

const formEnvolvido = ref({});

const resetForm = () => {
  formEnvolvido.value = {
    tipo_pessoa: 'Vítima',
    nome: '',
    nome_social: '',
    cpf: '',
    data_nascimento: '',
    idade_aparente: '',
    sexo: '',
    nome_mae: '',
    nome_pai: '',
    ocupacao: '',
    escolaridade: '',
    cep: '',
    uf: '',
    municipio: '',
    logradouro: '',
    bairro: '',
    numero: '',
    complemento: '',
    telefone: '',
    email: '',
  };
};

// Options
const tipoPessoaOptions = [
  { value: 'Vítima', label: 'Vítima' },
  { value: 'Testemunha', label: 'Testemunha' },
  { value: 'Solicitante', label: 'Solicitante' },
  { value: 'Proprietário', label: 'Proprietário' },
  { value: 'Outros', label: 'Outros' },
];

const sexoOptions = [
  { value: 'M', label: 'Masculino' },
  { value: 'F', label: 'Feminino' },
  { value: 'O', label: 'Outro' },
];

const escolaridadeOptions = [
  { value: 'fundamental_incompleto', label: 'Fundamental Incompleto' },
  { value: 'fundamental_completo', label: 'Fundamental Completo' },
  { value: 'medio_incompleto', label: 'Médio Incompleto' },
  { value: 'medio_completo', label: 'Médio Completo' },
  { value: 'superior_incompleto', label: 'Superior Incompleto' },
  { value: 'superior_completo', label: 'Superior Completo' },
];

// Função removida - formulário sempre visível

const cancelarFormulario = () => {
  resetForm();
  envolvidoEditIndex.value = null;
  // Formulário permanece visível, apenas reseta os campos
};

const editarEnvolvido = (index) => {
  formEnvolvido.value = { ...localData.value[index] };
  envolvidoEditIndex.value = index;
  // Scroll suave para o formulário
  setTimeout(() => {
    const formElement = document.querySelector('.rat-section-content');
    if (formElement) {
      formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 100);
};

const salvarEnvolvido = () => {
  if (!formEnvolvido.value.nome || !formEnvolvido.value.cpf) {
    alert('Nome e CPF são obrigatórios');
    return;
  }

  if (envolvidoEditIndex.value !== null) {
    localData.value[envolvidoEditIndex.value] = { ...formEnvolvido.value };
  } else {
    localData.value.push({ ...formEnvolvido.value });
  }

  emit('update:modelValue', localData.value);
  
  // Após salvar, reseta o formulário mas mantém visível para adicionar mais
  resetForm();
  envolvidoEditIndex.value = null;
  
  // Scroll suave para o topo do formulário
  setTimeout(() => {
    const formElement = document.querySelector('.rat-section-content');
    if (formElement) {
      formElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }, 100);
};

const removerEnvolvido = (index) => {
  if (confirm('Deseja realmente remover esta pessoa?')) {
    localData.value.splice(index, 1);
    emit('update:modelValue', localData.value);
  }
};

const { buscarCep } = useCep();

const buscarCepEnvolvido = async () => {
  if (formEnvolvido.value.cep) {
    const cepLimpo = formEnvolvido.value.cep.replace(/\D/g, '');
    if (cepLimpo.length === 8) {
      const resultado = await buscarCep(cepLimpo);
      if (resultado) {
        formEnvolvido.value.logradouro = resultado.logradouro || formEnvolvido.value.logradouro;
        formEnvolvido.value.bairro = resultado.bairro || formEnvolvido.value.bairro;
        formEnvolvido.value.municipio = resultado.localidade || formEnvolvido.value.municipio;
        formEnvolvido.value.uf = resultado.uf || formEnvolvido.value.uf;
      }
    }
  }
};

const formatarData = (data) => {
  if (!data) return '';
  const [ano, mes, dia] = data.split('-');
  return `${dia}/${mes}/${ano}`;
};

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      localData.value = [...newValue];
    }
  },
  { deep: true }
);

// Inicializa o form vazio
resetForm();
</script>
