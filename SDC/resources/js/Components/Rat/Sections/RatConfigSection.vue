<template>
  <RatCollapsibleSection
    section-id="configuracoes"
    title="Configurações do RAT"
    subtitle="Configurações gerais e unidade responsável"
    icon-class="rat-section-icon-purple"
  >
    <template #icon>
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </template>
    <div class="space-y-4 sm:space-y-5">
      <ToggleField
        :model-value="modelValue.tem_vistoria"
        label="Realizou Vistoria Imobiliária?"
        description="Habilita a aba de vistoria técnica"
        :icon="ClipboardIcon"
        @update:model-value="onToggleVistoria"
      />

      <!-- Unidade Responsável -->
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">Unidade Responsável</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <FormField
            label="Município"
            :model-value="modelValue.uni_responsavel_municipio"
            @update:model-value="update('uni_responsavel_municipio', $event)"
          />
          <FormField
            label="Código da Unidade"
            :model-value="modelValue.uni_responsavel_codigo"
            @update:model-value="update('uni_responsavel_codigo', $event)"
          />
          <FormField
            label="Nome da Unidade"
            :model-value="modelValue.uni_responsavel_unidade"
            @update:model-value="update('uni_responsavel_unidade', $event)"
          />
        </div>
      </div>

      <!-- Número do Boletim de Ocorrência (gera o BOS) -->
      <div>
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Número do Boletim de Ocorrência</p>
        <p class="text-xs text-slate-500 mb-3">Preencha os três campos para gerar automaticamente o Número do BOS</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <FormField
            label="Ano do BO"
            type="number"
            :model-value="modelValue.uni_bo_ano"
            placeholder="Ex: 2026"
            @update:model-value="update('uni_bo_ano', $event)"
          />
          <FormField
            label="Nº Sequencial do BO"
            type="number"
            :model-value="modelValue.uni_bo_sequencial"
            placeholder="Ex: 61"
            @update:model-value="update('uni_bo_sequencial', $event)"
          />
          <FormField
            label="Código da Unidade (BO)"
            type="number"
            :model-value="modelValue.uni_bo_cod_unidade"
            placeholder="Ex: 001"
            @update:model-value="update('uni_bo_cod_unidade', $event)"
          />
        </div>
      </div>

      <!-- Narrativa / Descrição -->
      <FormField
        type="textarea"
        label="Narrativa / Descrição da Ocorrência"
        :model-value="modelValue.descricao"
        :rows="4"
        @update:model-value="update('descricao', $event)"
      />
    </div>
  </RatCollapsibleSection>
</template>

<script setup>
import ClipboardIcon from '@/Components/Icons/ClipboardIcon.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import ToggleField from '@/Components/Molecules/Form/ToggleField.vue';
import RatCollapsibleSection from './RatCollapsibleSection.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ tem_vistoria: false }),
  },
});

const emit = defineEmits(['update:modelValue']);

function update(field, value) {
  emit('update:modelValue', { ...props.modelValue, [field]: value });
}

function onToggleVistoria(value) {
  emit('update:modelValue', { ...props.modelValue, tem_vistoria: value });
}
</script>
