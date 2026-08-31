<template>
  <div>
    <div
      v-if="saldos.length"
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 md:gap-6"
    >
      <EstoqueAhCard
        v-for="linha in saldos"
        :key="`${linha.deposito_id}-${linha.material_id}`"
        :saldo="linha"
      />
    </div>

    <ListEmptyState
      v-else
      :icon="ArchiveBoxIcon"
      title="Nenhum saldo encontrado"
      helper="Ajuste o depósito ou o nome do material."
    />
  </div>
</template>

<script setup>
import EstoqueAhCard from '@/Components/Molecules/AjudaHumanitaria/EstoqueAhCard.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import ArchiveBoxIcon from '@/Components/Icons/ArchiveBoxIcon.vue';

defineProps({
  saldos: {
    type: Array,
    default: () => [],
  },
});
</script>
