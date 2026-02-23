<template>
  <div class="tdap-product-card bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4 hover:border-cyan-500/50 dark:hover:border-cyan-500/50 transition-all">
    <!-- Header -->
    <div class="card-header flex items-start justify-between mb-3">
      <div>
        <h3 class="text-base font-semibold text-slate-900 dark:text-white">{{ product.codigo }}</h3>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ product.nome }}</p>
      </div>
      <ProductTypeBadge :type="product.tipo" />
    </div>

    <!-- Body -->
    <div class="card-body grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
      <div class="field">
        <p class="text-xs text-slate-500 dark:text-slate-400">Grupo Risco</p>
        <span :class="[
          'inline-flex px-2 py-1 rounded text-xs font-medium mt-1',
          product.grupo_risco === 'ALIMENTO' ? 'bg-green-100 dark:bg-green-500/15 text-green-800 dark:text-green-300 ring-1 ring-green-500/25' :
          product.grupo_risco === 'QUIMICO' ? 'bg-yellow-100 dark:bg-yellow-500/15 text-yellow-800 dark:text-yellow-300 ring-1 ring-yellow-500/25' :
          'bg-gray-100 dark:bg-gray-500/15 text-gray-800 dark:text-gray-300 ring-1 ring-gray-500/25'
        ]">
          {{ product.grupo_risco }}
        </span>
      </div>

      <div class="field">
        <p class="text-xs text-slate-500 dark:text-slate-400">Estoque Mínimo</p>
        <p class="text-sm text-slate-900 dark:text-white font-medium mt-1">{{ product.estoque_minimo }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import ProductTypeBadge from '@/Components/Atoms/Tdap/ProductTypeBadge.vue';

defineProps({
  product: {
    type: Object,
    required: true,
  },
});
</script>

<style scoped>
.tdap-product-card {
  transition: all 0.2s ease-in-out;
}

.tdap-product-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}
</style>
