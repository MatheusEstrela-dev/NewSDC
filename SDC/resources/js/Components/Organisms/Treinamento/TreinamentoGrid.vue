<script setup>
import TreinamentoCard from '@/Components/Molecules/Treinamento/TreinamentoCard.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';

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
    <div v-if="treinamentos.length === 0" class="text-center py-12">
      <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
      <Text size="lg" color="muted" class="mt-4">
        Nenhum treinamento encontrado
      </Text>
      <Text size="sm" color="muted" class="mt-2">
        Tente ajustar os filtros ou crie um novo treinamento
      </Text>
    </div>

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
