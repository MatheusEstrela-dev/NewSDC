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

          <div>
            <Label required>Tipo</Label>
            <select
              v-model="formData.tipo"
              required
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
            >
              <option value="" disabled>Selecione...</option>
              <option value="comunitaria">Comunitaria</option>
              <option value="individual">Individual</option>
              <option value="escolar">Escolar</option>
            </select>
            <p v-if="errors.tipo" class="mt-1 text-xs text-red-400">{{ errors.tipo }}</p>
          </div>

          <div>
            <Label>Status</Label>
            <select
              v-model="formData.status"
              class="w-full px-4 py-2.5 rounded-lg bg-slate-800 border border-slate-700 text-slate-100 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
            >
              <option value="ativa">Ativa</option>
              <option value="pendente">Pendente</option>
              <option value="inativa">Inativa</option>
              <option value="em_obras">Em Obras</option>
            </select>
          </div>

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
      <div class="flex justify-end gap-3">
        <Button variant="ghost" type="button" @click="$emit('cancel')">Cancelar</Button>
        <Button variant="primary" type="submit" :loading="loading">
          {{ submitLabel }}
        </Button>
      </div>
    </div>
  </form>
</template>

<script setup>
import CardBase from '@/Components/Atoms/Card/CardBase.vue';
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Label from '@/Components/Atoms/Typography/Label.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

defineProps({
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  submitLabel: { type: String, default: 'Salvar' },
});

defineEmits(['submit', 'cancel']);
</script>
