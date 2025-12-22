<template>
  <div class="flex items-end gap-2">
    <button
      type="button"
      @click="$emit('search')"
      class="p-2 rounded-lg text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 transition-all duration-200"
      title="Pesquisar"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
    </button>
    <button
      type="button"
      @click="$emit('clear')"
      class="p-2 rounded-lg text-red-400 hover:text-red-300 hover:bg-red-500/10 transition-all duration-200"
      title="Limpar filtros"
    >
      <XMarkIcon class="w-5 h-5" />
    </button>
  </div>
</template>

<script setup>
import XMarkIcon from '../../Icons/XMarkIcon.vue';

defineEmits(['search', 'clear']);
</script>
