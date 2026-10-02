<template>
  <div class="grid gap-4 md:grid-cols-2">
    <template v-for="campo in CAMPOS_ACESSO" :key="campo.key">
      <FormSelect
        v-if="campo.key === 'tipo_documento'"
        v-model="form[campo.key]"
        :label="campo.rotulo"
        :options="TIPOS_DOCUMENTO"
        :required="campo.required"
        :error="form.errors[campo.key]"
      />
      <FormTextarea
        v-else-if="campo.textarea"
        v-model="form[campo.key]"
        class="md:col-span-2"
        :label="campo.rotulo"
        :rows="3"
        :error="form.errors[campo.key]"
      />
      <FormField
        v-else
        v-model="form[campo.key]"
        :label="campo.rotuloFormulario ?? campo.rotulo"
        :type="campo.type || 'text'"
        :required="campo.required"
        :maxlength="campo.max || 255"
        :error="form.errors[campo.key]"
      />
    </template>
  </div>
</template>

<script setup>
import FormField from '@/Components/Molecules/Form/FormField.vue';
import FormSelect from '@/Components/Molecules/Form/FormSelect.vue';
import FormTextarea from '@/Components/Molecules/Form/FormTextarea.vue';
import { CAMPOS_ACESSO, TIPOS_DOCUMENTO } from '@/Support/acessos';

defineProps({ form: { type: Object, required: true } });
</script>
