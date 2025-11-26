<template>
  <div class="bg-slate-800 rounded-lg shadow-lg border border-slate-700/50 p-4 sm:p-6">
    <h3 class="text-xs sm:text-sm font-semibold text-slate-400 uppercase tracking-wider mb-3 sm:mb-4">
      Ações do Usuário
    </h3>
    <div class="flex flex-col space-y-2 sm:space-y-3">
      <button
        @click="$emit('save')"
        class="w-full text-white bg-blue-600 hover:bg-blue-500 focus:ring-4 focus:ring-blue-500/30 font-medium rounded-lg text-xs sm:text-sm px-4 sm:px-5 py-2.5 sm:py-3 text-center transition-all shadow-lg hover:shadow-blue-500/20 flex justify-center items-center gap-2"
      >
        <SaveIcon class="w-4 h-4 flex-shrink-0" />
        <span class="whitespace-nowrap">Salvar Alterações</span>
      </button>
      <button
        @click="$emit('save-draft')"
        class="w-full text-slate-300 bg-slate-700 hover:bg-slate-600 focus:ring-4 focus:ring-slate-500/30 font-medium rounded-lg text-xs sm:text-sm px-4 sm:px-5 py-2.5 sm:py-3 text-center transition-all border border-slate-600"
      >
        Salvar como Rascunho
      </button>
      <button
        @click="$emit('archive')"
        class="w-full text-red-400 border border-red-500/30 hover:bg-red-900/20 focus:ring-4 focus:ring-red-500/10 font-medium rounded-lg text-xs sm:text-sm px-4 sm:px-5 py-2.5 sm:py-3 text-center transition-all mt-2"
      >
        Arquivar Empreendimento
      </button>
    </div>
  </div>
</template>

<script setup>
import SaveIcon from '../Icons/SaveIcon.vue';

defineEmits(['save', 'save-draft', 'archive']);
</script>

