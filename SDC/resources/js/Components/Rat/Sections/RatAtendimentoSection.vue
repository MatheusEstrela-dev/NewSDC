<template>
  <RatCollapsibleSection
    section-id="atendimento"
    title="Atendimento"
    subtitle="Data e horário do fato e atividades"
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
        :model-value="modelValue.data_fato"
        required
        @update:model-value="handleDataFato"
      />
      <FormField
        label="Início da Atividade"
        type="datetime-local"
        :model-value="modelValue.data_inicio_atividade"
        required
        @update:model-value="handleDataInicio"
      />
      <FormField
        label="Término da Atividade"
        type="datetime-local"
        :model-value="modelValue.data_termino_atividade"
        @update:model-value="handleDataTermino"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      data_fato: '',
      data_inicio_atividade: '',
      data_termino_atividade: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const handleDataFato = (value) => {
  emit('update:modelValue', { ...props.modelValue, data_fato: value });
};

const handleDataInicio = (value) => {
  emit('update:modelValue', { ...props.modelValue, data_inicio_atividade: value });
};

const handleDataTermino = (value) => {
  emit('update:modelValue', { ...props.modelValue, data_termino_atividade: value });
};

const handleDataComunicacao = (value) => {
  emit('update:modelValue', { ...props.modelValue, com_ocorrencia_data: value });
};
</script>
