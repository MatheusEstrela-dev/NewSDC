<script setup>
defineProps({
  estado: { type: String, required: true, validator: (v) => ['ocioso', 'salvando', 'salvo', 'erro'].includes(v) },
});

const emit = defineEmits(['tentar-novamente']);
</script>

<template>
  <p class="min-h-[1.25rem] text-xs" role="status" aria-live="polite">
    <span v-if="estado === 'salvando'" class="text-slate-500 dark:text-slate-400">Salvando...</span>
    <span v-else-if="estado === 'salvo'" class="text-emerald-600 dark:text-emerald-400">Resposta salva</span>
    <span v-else-if="estado === 'erro'" class="text-red-600 dark:text-red-400">
      Não foi possível salvar.
      <button type="button" class="font-semibold underline" @click="emit('tentar-novamente')">Tentar novamente</button>
    </span>
  </p>
</template>
