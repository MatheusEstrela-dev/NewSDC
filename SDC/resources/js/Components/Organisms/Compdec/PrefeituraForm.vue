<template>
  <form @submit.prevent="handleSubmit" class="prefeitura-form">
    <!-- Dados do Prefeito -->
    <div class="form-section">
      <Heading level="4" class="mb-3">Dados do Prefeito</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.prefeito_nome"
          label="Nome do Prefeito"
          placeholder="Nome completo"
          :error="errors.prefeito_nome"
        />

        <TextInput
          v-model="formData.prefeito_email"
          label="E-mail"
          type="email"
          placeholder="prefeito@municipio.mg.gov.br"
          :error="errors.prefeito_email"
        />

        <TextInput
          v-model="formData.prefeito_telefone"
          label="Telefone"
          placeholder="(31) 3333-3333"
          :error="errors.prefeito_telefone"
        />

        <TextInput
          v-model="formData.prefeito_celular"
          label="Celular"
          placeholder="(31) 99999-9999"
          :error="errors.prefeito_celular"
        />
      </div>
    </div>

    <!-- Endereco -->
    <div class="form-section">
      <Heading level="4" class="mb-3">Endereco</Heading>
      <div class="form-grid">
        <TextInput
          v-model="formData.endereco"
          label="Logradouro"
          placeholder="Rua, numero"
          :error="errors.endereco"
          class="full-width"
        />

        <TextInput
          v-model="formData.bairro"
          label="Bairro"
          placeholder="Centro"
          :error="errors.bairro"
        />

        <TextInput
          v-model="formData.cep"
          label="CEP"
          placeholder="00000-000"
          :error="errors.cep"
        />

        <TextInput
          v-model="formData.latitude"
          label="Latitude"
          type="number"
          step="0.0000001"
          placeholder="-19.9166813"
          :error="errors.latitude"
        />

        <TextInput
          v-model="formData.longitude"
          label="Longitude"
          type="number"
          step="0.0000001"
          placeholder="-43.9344931"
          :error="errors.longitude"
        />
      </div>
    </div>

    <!-- INSS -->
    <div class="form-section">
      <Heading level="4" class="mb-3">INSS</Heading>
      <div class="form-grid">
        <SelectInput
          v-model="formData.inss_tem_cobranca"
          label="Possui Cobranca de INSS"
          :options="[
            { value: false, label: 'Nao' },
            { value: true, label: 'Sim' },
          ]"
          :error="errors.inss_tem_cobranca"
        />

        <TextInput
          v-if="formData.inss_tem_cobranca"
          v-model="formData.inss_aliquota"
          label="Aliquota (%)"
          type="number"
          step="0.01"
          placeholder="2.50"
          :error="errors.inss_aliquota"
        />

        <TextInput
          v-if="formData.inss_tem_cobranca"
          v-model="formData.inss_lei_cobranca"
          label="Lei de Cobranca"
          placeholder="Lei municipal n. ..."
          :error="errors.inss_lei_cobranca"
        />

        <TextInput
          v-if="formData.inss_tem_cobranca"
          v-model="formData.inss_responsavel"
          label="Responsavel pelo Recolhimento"
          placeholder="Nome do responsavel"
          :error="errors.inss_responsavel"
        />
      </div>
    </div>

    <!-- Acoes -->
    <div class="form-actions">
      <Button
        type="button"
        variant="outline"
        @click="handleCancel"
      >
        Cancelar
      </Button>

      <Button
        type="submit"
        variant="primary"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : 'Salvar Prefeitura' }}
      </Button>
    </div>
  </form>
</template>

<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import TextInput from '@/Components/Atoms/Input/TextInput.vue';
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';

const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

function handleSubmit() {
  emit('submit', props.formData);
}

function handleCancel() {
  emit('cancel');
}
</script>

<style scoped>
.prefeitura-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  padding: 1.25rem;
  border-radius: 0.75rem;
  border: 1px solid;
  @apply bg-slate-50 dark:bg-slate-900/40 border-slate-200 dark:border-slate-700/50;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.full-width {
  grid-column: 1 / -1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 0.5rem;
}
</style>
