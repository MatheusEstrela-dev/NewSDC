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
                v-model="localData.tipo_envolvimento"
                :options="tipoEnvolvimentoOptions"
                required
              />
              <FormField
                label="CPF"
                v-model="localData.cpf"
                mask="cpf"
              />
            </div>
            <div class="rat-grid-2">
              <FormField
                label="Nome Completo"
                v-model="localData.nome"
                placeholder="Nome da pessoa"
                required
              />
              <FormField
                label="RG"
                v-model="localData.rg"
                placeholder="Identidade"
              />
            </div>
            <div class="rat-grid-3">
              <FormField
                label="Data de Nascimento"
                type="date"
                v-model="localData.data_nascimento"
              />
              <FormSelect
                label="Sexo"
                v-model="localData.sexo"
                :options="sexoOptions"
              />
              <FormField
                label="Telefone"
                v-model="localData.telefone"
                mask="phone"
              />
            </div>
          </div>

          <!-- Endereço -->
          <div class="pt-4 border-t border-slate-200 dark:border-slate-700/30 space-y-4">
            <div class="rat-grid-3">
              <FormField
                label="CEP"
                v-model="localData.cep"
                mask="cep"
              />
              <FormField
                label="Município"
                v-model="localData.municipio"
              />
              <FormField
                label="Estado/UF"
                v-model="localData.uf"
                maxlength="2"
              />
            </div>
            <FormField
              label="Endereço / Logradouro"
              v-model="localData.endereco"
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
  tipo_envolvimento: props.modelValue?.tipo_envolvimento || 'vitima',
  nome: props.modelValue?.nome || '',
  rg: props.modelValue?.rg || '',
  cpf: props.modelValue?.cpf || '',
  sexo: props.modelValue?.sexo || '',
  data_nascimento: props.modelValue?.data_nascimento || '',
  telefone: props.modelValue?.telefone || '',
  cep: props.modelValue?.cep || '',
  endereco: props.modelValue?.endereco || '',
  bairro: props.modelValue?.bairro || '',
  municipio: props.modelValue?.municipio || '',
  uf: props.modelValue?.uf || '',
});

const tipoEnvolvimentoOptions = [
  { value: 'vitima', label: 'Vítima' },
  { value: 'testemunha', label: 'Testemunha' },
  { value: 'solicitante', label: 'Solicitante' },
  { value: 'proprietario', label: 'Proprietário' },
  { value: 'outros', label: 'Outros' },
];

const sexoOptions = [
  { value: 'M', label: 'Masculino' },
  { value: 'F', label: 'Feminino' },
  { value: 'O', label: 'Outro' },
];

const { buscarCep } = useCep();

watch(
  () => localData.value.cep,
  async (newCep) => {
    if (!newCep) return;
    const cepLimpo = newCep.replace(/\D/g, '');
    if (cepLimpo.length !== 8) return;
    const resultado = await buscarCep(cepLimpo);
    if (resultado) {
      localData.value.endereco  = resultado.logradouro || localData.value.endereco;
      localData.value.bairro    = resultado.bairro     || localData.value.bairro;
      localData.value.municipio = resultado.localidade || localData.value.municipio;
      localData.value.uf        = resultado.uf         || localData.value.uf;
    }
  }
);

// Sincroniza local -> pai apenas se houver mudança real
watch(
  () => localData.value,
  (nv) => {
    if (JSON.stringify(nv) !== JSON.stringify(props.modelValue)) {
      emit('update:modelValue', nv);
    }
  },
  { deep: true }
);

// Sincroniza pai -> local apenas se os dados externos mudarem
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
