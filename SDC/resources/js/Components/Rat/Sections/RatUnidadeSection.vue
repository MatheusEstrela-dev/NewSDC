<template>
  <RatCollapsibleSection
    section-id="unidade"
    title="Unidade Responsável pelo Registro"
    subtitle="Dados da unidade de defesa civil responsável pelo atendimento"
    icon-class="rat-section-icon-default"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
      </svg>
    </template>

    <div class="rat-grid-3">
      <FormField
        label="Município"
        :model-value="modelValue.uni_responsavel_municipio"
        placeholder="Selecione o município"
        @update:model-value="emit('update:modelValue', { ...modelValue, uni_responsavel_municipio: $event })"
      />
      <FormField
        label="Código da Unidade"
        :model-value="modelValue.uni_responsavel_codigo"
        placeholder="Ex: 12345"
        @update:model-value="emit('update:modelValue', { ...modelValue, uni_responsavel_codigo: $event })"
      />
      <FormField
        label="Nome da Unidade"
        :model-value="modelValue.uni_responsavel_unidade"
        placeholder="Selecione a unidade"
        @update:model-value="emit('update:modelValue', { ...modelValue, uni_responsavel_unidade: $event })"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import FormField from '@/Components/Form/FormField.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({
      uni_responsavel_municipio: '',
      uni_responsavel_codigo: '',
      uni_responsavel_unidade: '',
    }),
  },
});

const emit = defineEmits(['update:modelValue']);
</script>
