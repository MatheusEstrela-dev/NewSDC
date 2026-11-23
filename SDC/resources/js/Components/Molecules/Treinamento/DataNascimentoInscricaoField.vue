<script setup>
import TextInput from '@/Components/Atoms/Input/TextInput.vue';

/*
 * Pergunta de data de nascimento da inscricao. Opcional: vazio vira null no
 * back. v-model recebe 'YYYY-MM-DD' ou ''.
 */
defineProps({
  modelValue: { type: String, default: '' },
  id: { type: String, default: 'inscricao-data-nascimento' },
});
defineEmits(['update:modelValue']);

// Data LOCAL (nao toISOString, que e UTC): a noite no Brasil o UTC ja e o dia
// seguinte e o campo aceitaria "hoje", que o servidor recusa (before:today).
const ontemLocal = new Date();
ontemLocal.setDate(ontemLocal.getDate() - 1);
const doisDigitos = (n) => String(n).padStart(2, '0');
const ontem = `${ontemLocal.getFullYear()}-${doisDigitos(ontemLocal.getMonth() + 1)}-${doisDigitos(ontemLocal.getDate())}`;
</script>

<template>
  <div>
    <label :for="id" class="mb-1 block text-sm font-medium text-slate-700 dark:text-slate-200">Data de nascimento</label>
    <TextInput
      :id="id"
      type="date"
      :model-value="modelValue"
      min="1900-01-02"
      :max="ontem"
      @update:model-value="$emit('update:modelValue', $event)"
    />
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
      Opcional. Usada apenas para a faixa etária nas estatísticas das capacitações.
    </p>
  </div>
</template>
