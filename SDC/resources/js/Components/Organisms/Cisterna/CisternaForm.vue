<template>
  <form @submit.prevent="$emit('submit', formData)">
    <div class="space-y-6">
      <!-- Identificacao -->
      <CardBase>
        <Heading level="3" class="mb-4">Identificacao</Heading>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField
            v-model="formData.codigo"
            label="Codigo"
            required
            :error="errors.codigo"
            :maxlength="60"
          />

          <FormField
            v-model="formData.nome"
            label="Nome"
            required
            :error="errors.nome"
            :maxlength="255"
          />

          <FormSelect
            v-model="formData.tipo"
            label="Tipo"
            required
            :options="tipoOptions"
            placeholder="Selecione..."
            :error="errors.tipo"
          />

          <FormSelect
            v-model="formData.status"
            label="Status"
            :options="statusOptions"
            placeholder="Selecione..."
            :error="errors.status"
          />

          <FormField
            v-model.number="formData.municipio_id"
            type="number"
            label="ID Municipio"
            required
            :error="errors.municipio_id"
            hint="ID numerico do municipio na base"
          />

          <FormField
            v-model.number="formData.capacidade_litros"
            type="number"
            label="Capacidade (litros)"
            :error="errors.capacidade_litros"
          />
        </div>
      </CardBase>

      <!-- Localizacao -->
      <CardBase>
        <Heading level="3" class="mb-4">Localizacao</Heading>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormTextarea
            v-model="formData.endereco"
            label="Endereco"
            :error="errors.endereco"
            :rows="2"
            class="md:col-span-2"
          />

          <FormField
            v-model="formData.latitude"
            label="Latitude"
            :error="errors.latitude"
            placeholder="-19.9"
          />

          <FormField
            v-model="formData.longitude"
            label="Longitude"
            :error="errors.longitude"
            placeholder="-43.9"
          />
        </div>
      </CardBase>

      <!-- Responsavel + Datas -->
      <CardBase>
        <Heading level="3" class="mb-4">Responsavel e Datas</Heading>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <FormField
            v-model="formData.responsavel_nome"
            label="Nome do Responsavel"
            :error="errors.responsavel_nome"
            :maxlength="255"
          />

          <FormField
            v-model="formData.responsavel_telefone"
            label="Telefone"
            :error="errors.responsavel_telefone"
            placeholder="DDD + numero"
            :maxlength="20"
          />

          <FormField
            v-model="formData.data_instalacao"
            type="date"
            label="Data de Instalacao"
            :error="errors.data_instalacao"
          />
        </div>
      </CardBase>

      <!-- Observacoes -->
      <CardBase>
        <FormTextarea
          v-model="formData.observacoes"
          label="Observacoes"
          :error="errors.observacoes"
          :rows="3"
        />
      </CardBase>

      <!-- Acoes -->
      <FormActions
        cancel-label="Cancelar"
        :submit-label="submitLabel"
        :loading="loading"
        @cancel="$emit('cancel')"
        @submit="$emit('submit', formData)"
      />
    </div>
  </form>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

defineProps({
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);

const tipoOptions = [
  { value: 'comunitaria', label: 'Comunitaria' },
  { value: 'individual', label: 'Individual' },
  { value: 'escolar', label: 'Escolar' },
];

const statusOptions = [
  { value: 'ativa', label: 'Ativa' },
  { value: 'pendente', label: 'Pendente' },
  { value: 'inativa', label: 'Inativa' },
  { value: 'em_obras', label: 'Em obras' },
];
</script>
