<template>
  <div class="grid grid-cols-1 gap-4">
    <FormFileField
      v-model="form.arquivo"
      label="PDF"
      accept="application/pdf,.pdf"
      :hint="`Somente PDF, até ${limiteMb} MB.`"
      required
      :error="errors.arquivo"
    />
    <FormField
      v-model="form.numero"
      label="Versão"
      placeholder="1.0"
      required
      :maxlength="20"
      hint="Números separados por ponto, como 1.0 ou 2.1."
      :error="errors.numero"
    />
    <FormTextarea
      v-model="form.changelog"
      label="O que mudou"
      placeholder="Resumo das mudanças no fluxo em relação à versão anterior."
      :rows="3"
      :maxlength="5000"
      :error="errors.changelog"
    />
  </div>
</template>

<script setup>
/**
 * Campos de uma versao (PDF, numero, changelog). Usada no cadastro da nota e no
 * modal de nova versao -- o mesmo trio, a mesma validacao do backend.
 */
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormFileField from '@/Components/Molecules/Form/FormFileField.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';

defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
  limiteMb: { type: Number, default: 30 },
});
</script>
