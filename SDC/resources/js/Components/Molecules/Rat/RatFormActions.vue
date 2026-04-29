<template>
  <div v-if="!viewOnly" class="rat-actions-footer">
    <div class="flex items-center justify-center gap-3 px-4 py-4 flex-wrap">

      <!-- Botão Salvar — padrão em TODAS as abas -->
      <button
        type="button"
        :disabled="loading"
        @click="$emit('save')"
        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold
               bg-blue-600 hover:bg-blue-700 active:bg-blue-800
               text-white shadow-sm transition-colors duration-150
               disabled:opacity-60 disabled:cursor-not-allowed min-w-[120px] justify-center"
      >
        <svg
          v-if="loading"
          class="w-4 h-4 animate-spin"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
        </svg>
        <svg
          v-else
          class="w-4 h-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ loading ? 'Salvando...' : 'Salvar' }}
      </button>

      <!-- Botão Finalizar RAT — apenas quando showFinalize=true (Dados Gerais) -->
      <button
        v-if="showFinalize"
        type="button"
        :disabled="loading"
        @click="$emit('finalize')"
        class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg text-sm font-semibold
               bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800
               text-white shadow-sm transition-colors duration-150
               disabled:opacity-60 disabled:cursor-not-allowed min-w-[140px] justify-center"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="hidden sm:inline">Finalizar RAT</span>
        <span class="sm:hidden">Finalizar</span>
      </button>

    </div>
  </div>
</template>

<script setup>
defineProps({
  viewOnly:     { type: Boolean, default: false },
  loading:      { type: Boolean, default: false },
  showFinalize: { type: Boolean, default: false },
});

defineEmits(['save', 'finalize']);
</script>
