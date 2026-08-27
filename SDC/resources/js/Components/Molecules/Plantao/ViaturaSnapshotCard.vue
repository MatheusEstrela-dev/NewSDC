<script setup>
import CombustivelGauge from '@/Components/Atoms/Plantao/CombustivelGauge.vue';
import HodometroBadge from '@/Components/Atoms/Plantao/HodometroBadge.vue';

defineProps({
  snapshot: {
    type: Object,
    required: true,
  },
});
</script>

<template>
  <div
    class="flex gap-4 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
    :class="{ 'opacity-60': !snapshot.em_condicoes }"
  >
    <CombustivelGauge
      :percentual="snapshot.combustivel_percentual"
      :label="snapshot.combustivel_label"
      altura="h-24"
    />

    <div class="min-w-0 flex-1 space-y-1.5">
      <div class="flex flex-wrap items-baseline gap-2">
        <span class="font-semibold text-gray-900 dark:text-gray-100">
          {{ snapshot.prefixo }} - {{ snapshot.placa }}
        </span>
        <span
          v-if="snapshot.anotacao"
          class="rounded bg-blue-100 px-1.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/40 dark:text-blue-300"
        >
          {{ snapshot.anotacao }}
        </span>
        <span
          v-if="!snapshot.em_condicoes"
          class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/40 dark:text-red-300"
        >
          Fora de condicoes
        </span>
      </div>

      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-600 dark:text-gray-300">
        <HodometroBadge :valor="snapshot.hodometro" />
        <span>
          Alteracoes:
          <span class="font-medium">{{ snapshot.alteracoes || 'Sem alteracoes' }}</span>
        </span>
        <span v-if="snapshot.ultimo_condutor_nome">
          Ultimo condutor:
          <span class="font-medium">{{ snapshot.ultimo_condutor_nome }}</span>
        </span>
      </div>
    </div>
  </div>
</template>
