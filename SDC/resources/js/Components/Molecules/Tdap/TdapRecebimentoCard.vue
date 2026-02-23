<template>
  <div class="tdap-recebimento-card bg-slate-800/60 border border-slate-700/50 rounded-lg p-4 hover:border-emerald-500/50 dark:hover:border-emerald-500/50 transition-all">
    <!-- Header -->
    <div class="card-header flex items-start justify-between mb-3">
      <div>
        <h3 class="text-base font-semibold text-white">{{ recebimento.numero_recebimento }}</h3>
        <p class="text-xs text-slate-400 mt-1">NF: {{ recebimento.nota_fiscal }}</p>
      </div>
      <RecebimentoStatusBadge :status="recebimento.status" />
    </div>

    <!-- Body -->
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
      <div class="field">
        <p class="text-xs text-slate-400">Placa Veículo</p>
        <p class="text-sm text-white font-medium mt-1">{{ recebimento.placa_veiculo }}</p>
      </div>

      <div class="field">
        <p class="text-xs text-slate-400">Data Chegada</p>
        <p class="text-sm text-slate-300 mt-1">{{ formatDate(recebimento.data_chegada) }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import RecebimentoStatusBadge from '@/Components/Atoms/Tdap/RecebimentoStatusBadge.vue';

defineProps({
  recebimento: {
    type: Object,
    required: true,
  },
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<style scoped>
.tdap-recebimento-card {
  transition: all 0.2s ease-in-out;
}

.tdap-recebimento-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
