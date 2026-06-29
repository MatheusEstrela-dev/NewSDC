<script setup>
import { computed } from 'vue';
import PmdaWizardPanel from '@/Components/Molecules/Pmda/PmdaWizardPanel.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';

const props = defineProps({
  form: { type: Object, required: true },
  saving: { type: Boolean, default: false },
});

defineEmits(['next', 'prev']);

// cobra_iss e boolean no backend; o select trabalha com '1'/'0'.
const cobraIss = computed({
  get: () => (props.form.cobra_iss ? '1' : '0'),
  set: (v) => { props.form.cobra_iss = v === '1'; },
});
const opcoesIss = [
  { value: '1', label: 'Sim' },
  { value: '0', label: 'Não' },
];
</script>

<template>
  <PmdaWizardPanel
    :step="2"
    title="Informações sobre ISS"
    subtitle="Dados da prefeitura e alíquotas do imposto."
    :icon="BuildingOfficeIcon"
    :saving="saving"
    @next="$emit('next')"
    @prev="$emit('prev')"
  >
    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
      <div>
        <label class="pmda-field-label">Nome do Prefeito(a) <span class="req">*</span></label>
        <TextInput v-model="form.nome_prefeito" :maxlength="110" />
      </div>
      <div>
        <label class="pmda-field-label">Telefone Prefeitura <span class="req">*</span></label>
        <TextInput v-model="form.tel_prefeitura" :maxlength="20" />
      </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
      <div class="md:col-span-1">
        <label class="pmda-field-label">Endereço da Prefeitura <span class="req">*</span></label>
        <TextInput v-model="form.endereco" :maxlength="150" />
      </div>
      <div>
        <label class="pmda-field-label">Bairro <span class="req">*</span></label>
        <TextInput v-model="form.bairro" :maxlength="60" />
      </div>
      <div>
        <label class="pmda-field-label">CEP <span class="req">*</span></label>
        <TextInput v-model="form.cep" :maxlength="10" />
      </div>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
      <div>
        <label class="pmda-field-label">Recolhe ISS? <span class="req">*</span></label>
        <SelectInput v-model="cobraIss" :options="opcoesIss" placeholder="" />
      </div>
      <div>
        <label class="pmda-field-label">Alíquota % <span class="req">*</span></label>
        <TextInput v-model="form.aliquota_iss" type="number" />
      </div>
      <div>
        <label class="pmda-field-label">Número da Lei / Ano <span class="req">*</span></label>
        <TextInput v-model="form.num_lei_iss" :maxlength="30" />
      </div>
    </div>
  </PmdaWizardPanel>
</template>
