<template>
  <div class="flex items-center gap-3 justify-end">
    <button
      type="button"
      @click="$emit('search')"
      class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-blue-500/30 active:scale-95 flex items-center gap-2"
      title="Aplicar Filtros"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      Aplicar
    </button>
    <button
      type="button"
      @click="$emit('clear')"
      class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-sm transition-all duration-300 hover:scale-105 hover:shadow-lg hover:shadow-red-500/20 active:scale-95 flex items-center gap-2"
      title="Limpar Filtros"
    >
      <XMarkIcon class="w-4 h-4" />
      Limpar
    </button>
  </div>
</template>

<script setup>
import XMarkIcon from '../../Icons/XMarkIcon.vue';

defineEmits(['search', 'clear']);
</script>

