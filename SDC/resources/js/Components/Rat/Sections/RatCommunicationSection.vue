<template>
  <div class="rat-section-card">
    <div class="rat-section-header">
      <div class="rat-section-icon rat-section-icon-default">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
      </div>
      <div>
        <h3 class="rat-section-title">Comunicação da Ocorrência</h3>
        <p class="text-xs text-slate-500 mt-0.5">Informações sobre como e quando a ocorrência foi comunicada</p>
      </div>
    </div>

    <div class="rat-section-content">
      <div class="rat-grid-2">
        <FormField
          label="Data/Hora da Comunicação"
          type="datetime-local"
          :model-value="modelValue.com_ocorrencia_data"
          required
          @update:model-value="handleDataComunicacao"
          :error="errors.com_ocorrencia_data"
        />

        <FormSelect
          label="Como foi solicitado o atendimento"
          :model-value="modelValue.com_ocorrencia_atendimento"
          :options="tipoSolicitacaoOptions"
          placeholder="Selecione..."
          required
          @update:model-value="handleTipoAtendimento"
          :error="errors.com_ocorrencia_atendimento"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      com_ocorrencia_data: '',
      com_ocorrencia_atendimento: '',
    }),
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['update:modelValue']);

const localData = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  },
});

const handleDataComunicacao = (value) => {
  emit('update:modelValue', { ...props.modelValue, com_ocorrencia_data: value });
};

const handleTipoAtendimento = (value) => {
  emit('update:modelValue', { ...props.modelValue, com_ocorrencia_atendimento: value });
};

const tipoSolicitacaoOptions = [
  { value: 'telefone', label: 'Telefone' },
  { value: 'radio', label: 'Rádio' },
  { value: 'pessoal', label: 'Pessoal' },
  { value: 'sistema', label: 'Sistema' },
  { value: 'email', label: 'E-mail' },
  { value: 'outro', label: 'Outro' },
];
</script>
