<template>
  <form class="space-y-6 pb-8" @submit.prevent="$emit('enviar')">
    <PageHeader title="Nova demanda" description="Descreva o que precisa; o assunto define as informações pedidas" :icon-image="moduleIcon('demandas')" variant="gradient" :espaco-inferior="false" />

    <FormSection title="Assunto e prioridade" :cols="2">
      <FormSelect v-model="form.assunto_id" label="Assunto" :options="assuntos" placeholder="Selecione" :error="form.errors.assunto_id" />
      <FormSelect v-model="form.prioridade_simples" label="Prioridade" :options="prioridades" placeholder="" :error="form.errors.prioridade_simples" />
      <FormSelect v-if="podeGerir" v-model="form.solicitante_id" label="Solicitante (em nome de)" :options="usuarios" placeholder="Eu mesmo" :error="form.errors.solicitante_id" />
      <FormSelect v-if="podeGerir" v-model="form.responsavel_id" label="Responsável" :options="usuarios" placeholder="Definir depois" :error="form.errors.responsavel_id" />
    </FormSection>

    <FormSection v-if="campos.length" title="Informações do assunto" :cols="2">
      <CampoDinamico v-for="c in campos" :key="c.label" :campo="c" v-model="form.campos_customizados[c.label]" :erro="form.errors[`campos_customizados.${c.label}`]" />
    </FormSection>

    <FormSection title="Descrição" :cols="1">
      <FormField v-model="form.titulo" label="Título" placeholder="Resumo curto" required :error="form.errors.titulo" />
      <FormTextarea v-model="form.descricao" label="Descrição" :rows="6" placeholder="Detalhe a demanda" :error="form.errors.descricao" />
    </FormSection>

    <div class="flex justify-end gap-2">
      <Button type="button" variant="secondary" size="md" @click="$emit('cancelar')">Cancelar</Button>
      <Button type="submit" variant="primary" size="md" :disabled="form.processing">Abrir demanda</Button>
    </div>
  </form>
</template>

<script setup>
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import FormSection from '@/Components/Organisms/FormSection.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import CampoDinamico from '@/Components/Molecules/Demandas/CampoDinamico.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  form: { type: Object, required: true },
  assuntos: { type: Array, default: () => [] },
  campos: { type: Array, default: () => [] },
  prioridades: { type: Array, default: () => [] },
  usuarios: { type: Array, default: () => [] },
  podeGerir: { type: Boolean, default: false },
});
defineEmits(['enviar', 'cancelar']);
</script>
