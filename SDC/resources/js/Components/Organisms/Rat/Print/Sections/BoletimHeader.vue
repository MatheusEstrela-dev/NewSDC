<script setup>
defineProps({
  ocorrencia: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div class="card-header bg-primary text-white p-3" style="background-color: #003d82 !important;">
    <div class="flex items-center justify-between">
      <div style="flex: 0 0 80px;">
        <img src="/imagem/DEFESACIVILMG_400.png" alt="Defesa Civil MG" class="brasao-logo" style="max-width: 70px; height: auto;">
      </div>
      <div class="flex-grow text-center px-3">
        <h5 class="mb-2 font-bold text-white" style="font-size: 1.1rem;">SISTEMA INTEGRADO DE DEFESA CIVIL</h5>
        <h4 class="mb-0 font-bold text-white" style="font-size: 1.3rem;">BOLETIM DE OCORRENCIA SIMPLIFICADO</h4>
      </div>
      <div class="text-right" style="flex: 0 0 180px;">
        <div class="inline-block px-2 py-1" style="font-size: 0.75rem; background-color: #f5f5f5; color: #333;">
          <strong>BOS:</strong> {{ ocorrencia.numero_bos || 'N/A' }}
        </div>
      </div>
    </div>
  </div>
</template>
