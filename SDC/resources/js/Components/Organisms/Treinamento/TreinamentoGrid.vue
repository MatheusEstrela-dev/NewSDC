<script setup>
import TreinamentoCard from '@/Components/Molecules/Treinamento/TreinamentoCard.vue';
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';

const props = defineProps({
  treinamentos: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  canDelete: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['view', 'edit', 'delete']);
</script>

<template>
  <div class="treinamento-grid">
    <ListEmptyState
      v-if="treinamentos.length === 0"
      title="Nenhum treinamento encontrado"
      helper="Tente ajustar os filtros ou crie um novo treinamento"
    />

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <TreinamentoCard
        v-for="treinamento in treinamentos"
        :key="treinamento.id"
        :treinamento="treinamento"
        :can-edit="canEdit"
        :can-delete="canDelete"
        @view="emit('view', $event)"
        @edit="emit('edit', $event)"
        @delete="emit('delete', $event)"
      />
    </div>
  </div>
</template>
