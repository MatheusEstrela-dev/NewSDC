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
          v-model="localData.data_comunicacao"
          required
          :error="errors.data_comunicacao"
        />

        <FormSelect
          label="Como foi solicitado o atendimento"
          v-model="localData.tipo_solicitacao"
          :options="tipoSolicitacaoOptions"
          placeholder="Selecione..."
          required
          :error="errors.tipo_solicitacao"
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
      data_comunicacao: '',
      tipo_solicitacao: '',
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

const tipoSolicitacaoOptions = [
  { value: 'telefone', label: 'Telefone' },
  { value: '190', label: '190 - Emergência' },
  { value: 'radio', label: 'Rádio' },
  { value: 'presencial', label: 'Presencial' },
  { value: 'whatsapp', label: 'WhatsApp' },
  { value: 'app', label: 'Aplicativo' },
  { value: 'email', label: 'E-mail' },
  { value: 'outros', label: 'Outros' },
];
</script>
