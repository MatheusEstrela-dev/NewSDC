<template>
  <div class="processo-create-template">
    <!-- Header -->
    <div class="page-header mb-6">
      <Heading level="h1" size="2xl">
        {{ isEditing ? 'Editar Processo' : 'Novo Reconhecimento de Desastre' }}
      </Heading>
      <Text size="sm" color="muted" class="mt-1">
        {{ isEditing ? 'Atualize os dados do processo' : 'Cadastre um novo processo de reconhecimento' }}
      </Text>
    </div>

    <!-- Formulario -->
    <ProcessoForm
      :form="form"
      :tipos-desastre="tiposDesastre"
      :cobrades="cobrades"
      :municipios="municipios"
      :redecs="redecs"
      :status-options="statusOptions"
      :analistas="analistas"
      :submit-label="isEditing ? 'Atualizar Processo' : 'Criar Processo'"
      :is-editing="isEditing"
      @submit="handleSubmit"
      @cancel="$emit('cancel')"
    />
  </div>
</template>

<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import ProcessoForm from '@/Components/Organisms/Decretacoes/ProcessoForm.vue';

const props = defineProps({
  form: {
    type: Object,
    required: true,
  },
  tiposDesastre: {
    type: Array,
    default: () => [],
  },
  cobrades: {
    type: Array,
    default: () => [],
  },
  municipios: {
    type: Array,
    default: () => [],
  },
  redecs: {
    type: Array,
    default: () => [],
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  analistas: {
    type: Array,
    default: () => [],
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['submit', 'cancel']);

function handleSubmit(formData) {
  emit('submit', formData);
}
</script>

<style scoped>
.processo-create-template {
  @apply w-full;
}
</style>
