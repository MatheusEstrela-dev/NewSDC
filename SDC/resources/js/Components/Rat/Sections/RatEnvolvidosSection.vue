<template>
  <fieldset :disabled="props.viewOnly" style="border:none;padding:0;margin:0;min-width:0;">
    <div class="rat-section-card">
      <div class="rat-section-header">
        <div class="rat-section-icon rat-section-icon-purple">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
          </svg>
        </div>
        <div>
          <h3 class="rat-section-title">Dados do Envolvido</h3>
          <p class="text-xs text-slate-500 mt-0.5">Informações pessoais e de contato</p>
        </div>
      </div>

      <div class="rat-section-content">
        <div class="space-y-6">
          <!-- Dados Pessoais -->
          <div class="space-y-4">
            <div class="rat-grid-2">
              <FormSelect
                label="Tipo de Envolvimento"
                v-model="localData.g_tipo_pessoa"
                :options="tipoEnvolvimentoOptions"
                required
              />
              <FormField
                label="CPF"
                v-model="localData.p_cpf"
                mask="cpf"
              />
            </div>
            <div class="rat-grid-2">
              <FormField
                label="Nome Completo"
                v-model="localData.p_nome_completo"
                placeholder="Nome da pessoa"
                required
              />
              <FormField
                label="RG"
                v-model="localData.p_rg"
                placeholder="Identidade"
              />
            </div>
            <div class="rat-grid-3">
              <FormField
                label="Data de Nascimento"
                type="date"
                :modelValue="localData.p_data_nascimento"
                @update:modelValue="handleDateNascimento"
              />
              <FormSelect
                label="Sexo"
                v-model="localData.p_sexo"
                :options="sexoOptions"
              />
              <FormField
                label="Telefone"
                v-model="localData.p_telefone"
                mask="phone"
              />
            </div>
          </div>

          <!-- Endereço -->
          <div class="pt-4 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
            <div class="rat-grid-3">
              <FormField
                label="CEP"
                v-model="localData.p_end_cep"
                mask="cep"
                @blur="buscarCepLocal"
              />
              <FormField
                label="Cidade"
                v-model="localData.p_end_cidade"
              />
              <FormField
                label="Estado/UF"
                v-model="localData.p_end_uf"
                maxlength="2"
              />
            </div>
            <div class="rat-grid-2">
              <FormField
                label="Logradouro"
                v-model="localData.p_end_logradouro"
              />
              <FormField
                label="Número"
                v-model="localData.p_end_numero"
              />
            </div>
            <FormField
              label="Bairro"
              v-model="localData.p_end_bairro"
            />
          </div>
        </div>
      </div>
    </div>
  </fieldset>
</template>

<script setup>
import { useCep } from '@/composables/location';
import { ref, watch } from 'vue';
import FormField from '../../Form/FormField.vue';
import FormSelect from '../../Form/FormSelect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({}),
  },
  viewOnly: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue']);

// localData gerencia apenas UM envolvido agora
const localData = ref({
  id: props.modelValue?.id || Date.now(),
  g_tipo_pessoa: props.modelValue?.g_tipo_pessoa || 'vítima',
  p_nome_completo: props.modelValue?.p_nome_completo || '',
  p_rg: props.modelValue?.p_rg || '',
  p_cpf: props.modelValue?.p_cpf || '',
  p_sexo: props.modelValue?.p_sexo || '',
  p_data_nascimento: props.modelValue?.p_data_nascimento || '',
  p_telefone: props.modelValue?.p_telefone || '',
  p_end_cep: props.modelValue?.p_end_cep || '',
  p_end_logradouro: props.modelValue?.p_end_logradouro || '',
  p_end_numero: props.modelValue?.p_end_numero || '',
  p_end_bairro: props.modelValue?.p_end_bairro || '',
  p_end_cidade: props.modelValue?.p_end_cidade || '',
  p_end_uf: props.modelValue?.p_end_uf || '',
  g_lesao_grau: props.modelValue?.g_lesao_grau || '',
});

const tipoEnvolvimentoOptions = [
  { value: 'vítima', label: 'Vítima' },
  { value: 'agressor', label: 'Agressor' },
  { value: 'testemunha', label: 'Testemunha' },
  { value: 'agente', label: 'Agente' },
  { value: 'outro', label: 'Outro' },
];

const sexoOptions = [
  { value: 'M', label: 'Masculino' },
  { value: 'F', label: 'Feminino' },
  { value: 'O', label: 'Outro' },
];

const { buscarCep } = useCep();

const formatDateForBackend = (isoDate) => {
  if (!isoDate) return '';
  if (typeof isoDate !== 'string') return '';
  // Se já está em formato dd/mm/aaaa, retorna como está
  if (/^\d{2}\/\d{2}\/\d{4}/.test(isoDate)) {
    return isoDate;
  }
  // Se é ISO date (YYYY-MM-DD), converte
  const [year, month, day] = isoDate.split('-');
  return `${day}/${month}/${year}`;
};

const handleDateNascimento = (value) => {
  // Mantém em ISO para o input type="date", a conversão acontece no backend
  localData.value.p_data_nascimento = value;
};

const buscarCepLocal = async () => {
  if (localData.value.p_end_cep) {
    const cepLimpo = localData.value.p_end_cep.replace(/\D/g, '');
    if (cepLimpo.length === 8) {
      const resultado = await buscarCep(cepLimpo);
      if (resultado) {
        localData.value.p_end_logradouro = resultado.logradouro || localData.value.p_end_logradouro;
        localData.value.p_end_cidade = resultado.localidade || localData.value.p_end_cidade;
        localData.value.p_end_uf = resultado.uf || localData.value.p_end_uf;
      }
    }
  }
};

watch(
  localData,
  (newValue) => {
    emit('update:modelValue', newValue);
  },
  { deep: true }
);

watch(
  () => props.modelValue,
  (newValue) => {
    if (newValue) {
      const currentStr = JSON.stringify(localData.value);
      const newStr = JSON.stringify(newValue);
      if (currentStr !== newStr) {
        localData.value = { ...localData.value, ...newValue };
      }
    }
  },
  { deep: true }
);
</script>
