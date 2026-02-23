<template>
  <div class="tdap-movimentacao-card bg-slate-800/60 border border-slate-700/50 rounded-lg p-4 hover:border-amber-500/50 dark:hover:border-amber-500/50 transition-all">
    <!-- Header -->
    <div class="card-header flex items-start justify-between mb-3">
      <div>
        <h3 class="text-base font-semibold text-white">{{ movimentacao.numero_movimentacao }}</h3>
        <p class="text-xs text-slate-400 mt-1">{{ movimentacao.product?.nome || '-' }}</p>
      </div>
      <MovimentacaoTypeBadge :type="movimentacao.tipo" />
    </div>

    <!-- Body -->
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
      <div class="field">
        <p class="text-xs text-slate-400">Quantidade</p>
        <p class="text-sm text-white font-medium mt-1">{{ movimentacao.quantidade }}</p>
      </div>

      <div class="field">
        <p class="text-xs text-slate-400">Data</p>
        <p class="text-sm text-slate-300 mt-1">{{ formatDate(movimentacao.data_movimentacao) }}</p>
      </div>

      <div class="field col-span-1 sm:col-span-2">
        <p class="text-xs text-slate-400">Origem → Destino</p>
        <p class="text-sm text-slate-300 mt-1">
          {{ movimentacao.origem || '-' }} → {{ movimentacao.destino || '-' }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import MovimentacaoTypeBadge from '@/Components/Atoms/Tdap/MovimentacaoTypeBadge.vue';

defineProps({
  movimentacao: {
    type: Object,
    required: true,
  },
});

const formatDate = (date) => {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('pt-BR', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>

<style scoped>
.tdap-movimentacao-card {
  transition: all 0.2s ease-in-out;
}

.tdap-movimentacao-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
