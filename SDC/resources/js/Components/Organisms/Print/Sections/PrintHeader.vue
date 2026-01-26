<script setup>
defineProps({
  titulo: {
    type: String,
    default: 'SISTEMA INTEGRADO DE DEFESA CIVIL',
  },
  subtitulo: {
    type: String,
    default: 'DOCUMENTO',
  },
  numero: {
    type: String,
    default: null,
  },
  labelNumero: {
    type: String,
    default: 'DOC',
  },
  logoSrc: {
    type: String,
    default: '/imagem/DEFESACIVILMG_400.png',
  },
});
</script>

<template>
  <div class="card-header bg-primary text-white p-3" style="background-color: #003d82 !important;">
    <div class="flex items-center justify-between">
      <div style="flex: 0 0 80px;">
        <img :src="logoSrc" alt="Defesa Civil MG" class="brasao-logo" style="max-width: 70px; height: auto;">
      </div>
      <div class="flex-grow text-center px-3">
        <h5 class="mb-2 font-bold text-white" style="font-size: 1.1rem;">{{ titulo }}</h5>
        <h4 class="mb-0 font-bold text-white" style="font-size: 1.3rem;">{{ subtitulo }}</h4>
      </div>
      <div class="text-right" style="flex: 0 0 180px;">
        <div v-if="numero" class="inline-block px-2 py-1" style="font-size: 0.75rem; background-color: #f5f5f5; color: #333;">
          <strong>{{ labelNumero }}:</strong> {{ numero }}
        </div>
      </div>
    </div>
  </div>
</template>
