<script setup>
import Button from '@/Components/Atoms/Button/Button.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';

defineProps({
  turno: {
    type: Object,
    required: true,
  },
  podeAceitar: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['conferir']);
</script>

<template>
  <div
    class="flex flex-col gap-3 rounded-lg border border-amber-300 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-700 dark:bg-amber-900/20"
  >
    <div class="flex items-start gap-3">
      <ExclamationTriangleIcon
        class-name="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400"
      />
      <div class="text-sm">
        <p class="font-semibold text-amber-900 dark:text-amber-200">
          Passagem de servico pendente de aceite
        </p>
        <p class="text-amber-800 dark:text-amber-300">
          {{ turno.plantonista_nome }} encerrou o turno de {{ turno.data }}
          ({{ turno.periodo }}) em {{ turno.encerrado_em }}.
          Confira as viaturas antes de aceitar.
        </p>
        <p
          v-if="turno.encerrado_por_terceiro"
          class="mt-1 font-medium text-amber-900 dark:text-amber-200"
        >
          Encerrado por {{ turno.encerrado_por_nome }} em nome de
          {{ turno.plantonista_nome }}.
        </p>
      </div>
    </div>

    <Button
      v-if="podeAceitar"
      variant="primary"
      size="md"
      @click="$emit('conferir')"
    >
      Conferir e aceitar
    </Button>
  </div>
</template>
