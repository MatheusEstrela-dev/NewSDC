<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import TreinamentoStatsCards from '@/Components/Organisms/Treinamento/TreinamentoStatsCards.vue';
import TreinamentoGrid from '@/Components/Organisms/Treinamento/TreinamentoGrid.vue';

const props = defineProps({
  treinamentos: {
    type: Array,
    default: () => [],
  },
  statistics: {
    type: Object,
    required: true,
  },
  pagination: {
    type: Object,
    default: null,
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  canManage: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['create', 'view', 'edit', 'delete', 'filter']);

const handleStatFilter = (statId) => {
  // Mapear ID do stat para filtro de status
  const statusMap = {
    total: null,
    planejados: 'PLANEJADO',
    em_andamento: 'EM_ANDAMENTO',
    concluidos: 'CONCLUIDO',
  };

  emit('filter', { status: statusMap[statId] });
};
</script>

<template>
  <div class="treinamentos-container">
    <!-- Header -->
    <div class="mb-6 flex justify-between items-center">
      <div>
        <Heading :level="2" class="text-slate-800 dark:text-slate-100">
          Treinamentos
        </Heading>
        <Text size="sm" color="muted" class="mt-1">
          Gestão de treinamentos e cursos
        </Text>
      </div>

      <button
        v-if="canManage"
        @click="emit('create')"
        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-sm hover:shadow-md transition-all flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Novo Treinamento
      </button>
    </div>

    <!-- Statistics Cards -->
    <TreinamentoStatsCards :statistics="statistics" class="mb-6" @filter="handleStatFilter" />

    <!-- Filtros (Simplificado para MVP) -->
    <div v-if="filters && Object.keys(filters).length > 0" class="mb-4 flex items-center gap-2">
      <Text size="sm" color="muted">
        Filtros ativos:
      </Text>
      <div v-for="(value, key) in filters" :key="key" class="flex items-center gap-1">
        <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
          {{ key }}: {{ value }}
        </span>
      </div>
    </div>

    <!-- Grid de Treinamentos -->
    <TreinamentoGrid
      :treinamentos="treinamentos"
      :can-edit="canManage"
      :can-delete="canManage"
      @view="emit('view', $event)"
      @edit="emit('edit', $event)"
      @delete="emit('delete', $event)"
    />

    <!-- Pagination -->
    <div v-if="pagination && pagination.last_page > 1" class="mt-6 flex justify-between items-center">
      <Text size="sm" color="muted">
        Mostrando {{ pagination.from }} a {{ pagination.to }} de {{ pagination.total }} resultados
      </Text>

      <div class="flex gap-2">
        <button
          :disabled="pagination.current_page === 1"
          class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Anterior
        </button>
        <div class="flex items-center px-4">
          <Text size="sm">
            Página {{ pagination.current_page }} de {{ pagination.last_page }}
          </Text>
        </div>
        <button
          :disabled="pagination.current_page === pagination.last_page"
          class="px-3 py-2 border border-slate-300 dark:border-slate-600 rounded hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Próxima
        </button>
      </div>
    </div>
  </div>
</template>
