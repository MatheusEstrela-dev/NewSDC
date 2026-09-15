<template>
  <form class="space-y-6" @submit.prevent="handleSubmit">
    <PrefeituraFormSections
      :form="formData"
      :errors="errors"
      :pode-editar="podeEditar"
      :campos-institucionais="false"
    />

    <FormActions
      submit-label="Salvar Prefeitura"
      :loading="loading"
      :disabled="!podeEditar"
      @cancel="handleCancel"
      @submit="handleSubmit"
    />
  </form>
</template>

<script setup>
import FormActions from '@/Components/Molecules/Form/FormActions.vue';
import PrefeituraFormSections from '@/Components/Organisms/Cedec/PrefeituraFormSections.vue';

/**
 * Formulario de prefeitura da aba do Compdec.
 *
 * Depois do refit ele nao tem campo proprio: os campos vivem em
 * Organisms/Cedec/PrefeituraFormSections.vue, o mesmo organismo que a tela estadual
 * do Cedec monta. Um formulario, dois donos. O que sobrou aqui e a API que
 * Tabs/PrefeituraTab.vue ja consumia, por isso a aba nao precisou mudar de forma.
 *
 * Antes deste refit o arquivo importava TextInput e SelectInput crus e carregava
 * style scoped com .form-section e .form-grid proprios -- a divergencia que o kernel
 * do projeto nomeia. Nada disso sobrou.
 *
 * camposInstitucionais fica em false de proposito: o UpsertPrefeituraRequest do
 * Compdec nao valida partido nem os contatos institucionais, e mostrar campo que o
 * backend descarta e pior que esconder.
 */
const props = defineProps({
  formData: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false },
  podeEditar: { type: Boolean, default: true },
});

const emit = defineEmits(['submit', 'cancel']);

function handleSubmit() {
  if (! props.podeEditar) {
    return;
  }

  emit('submit', props.formData);
}

function handleCancel() {
  emit('cancel');
}
</script>
