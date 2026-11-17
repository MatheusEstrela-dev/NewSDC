<script setup>
import SelectInput from '@/Components/Atoms/Input/SelectInput.vue';

/*
 * Pergunta de sexo da inscricao. Opcional: "Prefiro nao informar" vira vazio
 * (null no back). v-model recebe 'F', 'M' ou ''.
 */
defineProps({
  modelValue: { type: String, default: '' },
  id: { type: String, default: 'inscricao-sexo' },
});
defineEmits(['update:modelValue']);

const opcoes = [
  { value: 'F', label: 'Feminino' },
  { value: 'M', label: 'Masculino' },
  { value: '', label: 'Prefiro não informar' },
];
</script>

<template>
  <div>
    <label :for="id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Sexo</label>
    <SelectInput
      :id="id"
      :model-value="modelValue"
      :options="opcoes"
      placeholder=""
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Opcional. Usado apenas em estatísticas das capacitações.
    </p>
  </div>
</template>
