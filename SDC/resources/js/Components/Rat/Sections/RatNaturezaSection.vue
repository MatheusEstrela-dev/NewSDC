<template>
  <RatCollapsibleSection
    section-id="natureza"
    title="Natureza / COBRADE"
    subtitle="Classificação COBRADE e identificação da operação"
    icon-class="rat-section-icon-success"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
      </svg>
    </template>

    <div class="rat-grid-2">
      <FormSelect
        label="Codigo da Ocorrencia"
        :model-value="modelValue.nat_codigo"
        :options="codigoOcorrenciaOptions"
        placeholder="Selecione o Codigo da Ocorrencia"
        required
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_codigo: $event })"
      />
      <FormSelect
        label="COBRADE"
        :model-value="modelValue.nat_cobrade_id"
        :options="cobradeOptions"
        placeholder="Selecione o COBRADE"
        required
        @update:model-value="emit('update:modelValue', { ...modelValue, nat_cobrade_id: $event })"
      />
    </div>

    <FormField
      label="Nome da Operação (Opcional)"
      :model-value="modelValue.nat_nome_operacao"
      placeholder="Ex: Operação Chuvas de Verão"
      hint="Informe o nome da operação caso se aplique"
      @update:model-value="emit('update:modelValue', { ...modelValue, nat_nome_operacao: $event })"
    />
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import FormSelect from '@/Components/Form/FormSelect.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      nat_codigo: '',
      nat_cobrade_id: '',
      nat_nome_operacao: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);

const codigoOcorrenciaOptions = [
  { value: '1.1.1.0.0', label: '1.1.1.0.0 - Terremoto' },
  { value: '1.2.1.0.0', label: '1.2.1.0.0 - Inundação' },
  { value: '1.2.2.0.0', label: '1.2.2.0.0 - Enxurrada' },
  { value: '1.2.3.0.0', label: '1.2.3.0.0 - Alagamento' },
  { value: '1.3.2.1.0', label: '1.3.2.1.0 - Tempestade Local/Convectiva' },
  { value: '1.4.1.0.0', label: '1.4.1.0.0 - Deslizamento de Grande Dimensão' },
  { value: '1.4.2.0.0', label: '1.4.2.0.0 - Corrida de Massa' },
  { value: '1.5.1.0.0', label: '1.5.1.0.0 - Incêndio Florestal' },
  { value: '2.1.1.0.0', label: '2.1.1.0.0 - Incêndio em Área Urbana' },
  { value: '2.4.1.0.0', label: '2.4.1.0.0 - Desabamento/Colapso de Edificação' },
  { value: '2.5.1.0.0', label: '2.5.1.0.0 - Acidente de Trânsito' },
];

const cobradeOptions = [
  { value: '1', label: '1.3.2.1.0 - Tempestade Local/Convectiva' },
  { value: '2', label: '1.2.1.0.0 - Inundação' },
  { value: '3', label: '1.4.1.0.0 - Deslizamento' },
  { value: '4', label: '1.2.2.0.0 - Enxurrada' },
  { value: '5', label: '1.5.1.0.0 - Incêndio Florestal' },
];
</script>
