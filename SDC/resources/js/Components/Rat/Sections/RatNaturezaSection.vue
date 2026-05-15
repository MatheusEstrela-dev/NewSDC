<template>
  <RatCollapsibleSection
    section-id="natureza"
    title="Natureza da Ocorrência"
    subtitle="Classificação COBRADE e identificação da operação"
    icon-class="rat-section-icon-success"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
    </template>
    <div class="rat-grid-3">
      <FormField
        label="Código COBRADE"
        :model-value="modelValue.nat_codigo"
        placeholder="Ex: 1.2.1.0.0"
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_codigo: $event })"
      />
      <FormSelect
        label="Classificação COBRADE"
        :model-value="modelValue.nat_cobrade_id"
        :options="cobradeOptions"
        placeholder="Selecione a classificação..."
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_cobrade_id: $event })"
      />
      <FormField
        label="Tipo de Ocorrência"
        :model-value="modelValue.nat_ocorrencia"
        placeholder="Ex: Inundação, Deslizamento..."
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_ocorrencia: $event })"
      />
    </div>
    <FormField
      label="Nome da Operação (Opcional)"
      :model-value="modelValue.nat_nome_operacao"
      placeholder="Ex: Operação Chuvas de Verão"
      @update:model-value="emit('update:modelValue', { ...modelValue, nat_nome_operacao: $event })"
    />
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      nat_codigo: '',
      nat_cobrade_id: '',
      nat_ocorrencia: '',
      nat_nome_operacao: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const cobradeOptions = [
  { value: '1', label: '1.3.2.1.0 - Tempestade Local' },
  { value: '2', label: '1.2.1.0.0 - Inundação' },
  { value: '3', label: '1.1.3.3.1 - Deslizamento de Planície' },
];
</script>
