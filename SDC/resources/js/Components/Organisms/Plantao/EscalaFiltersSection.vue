<script setup>
/**
 * Filtros do calendario da escala, no mesmo molde do PlantaoFiltersSection.
 *
 * As opcoes de turno vem do SERVIDOR, nunca de lista fixa no componente: os
 * horarios sao cadastraveis em plantao_tipos_turno desde que o enum
 * PeriodoPlantao foi aposentado, e uma lista fixa aqui voltaria a esconder
 * qualquer turno novo do usuario.
 *
 * Os filtros NAO mexem em "meus proximos plantoes" nem nos cards: aqueles
 * descrevem o mes inteiro. Filtrar recorta so a grade da equipe.
 */
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import { computed } from 'vue';

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({}),
  },
  filterOptions: {
    type: Object,
    default: () => ({ tiposTurno: [], plantonistas: [] }),
  },
});

const emit = defineEmits(['filter-change', 'filter-reset']);

const turnoOptions = computed(() => [
  { value: '', label: 'Todos' },
  ...(props.filterOptions?.tiposTurno ?? []).map((t) => ({
    value: String(t.value),
    label: t.label,
  })),
]);

const plantonistaOptions = computed(() => [
  { value: '', label: 'Todos' },
  ...(props.filterOptions?.plantonistas ?? []).map((p) => ({
    value: String(p.value),
    label: p.label,
  })),
]);

const escopoOptions = [
  { value: '', label: 'Toda a equipe' },
  { value: '1', label: 'Somente meus plantoes' },
];

const updateFilter = (campo, valor) => {
  emit('filter-change', { ...props.filters, [campo]: valor || undefined });
};

const handleSearch = () => emit('filter-change', { ...props.filters });

const handleClear = () => emit('filter-reset');
</script>

<template>
  <FilterSection title="Filtros de Pesquisa" :columns="3" class="mb-6">
    <FilterField
      label="Turno"
      type="select"
      :model-value="filters.tipo_turno_id ? String(filters.tipo_turno_id) : ''"
      :options="turnoOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('tipo_turno_id', $event)"
    />

    <FilterField
      label="Plantonista"
      type="select"
      :model-value="filters.plantonista_id ? String(filters.plantonista_id) : ''"
      :options="plantonistaOptions"
      placeholder="Todos"
      @update:model-value="updateFilter('plantonista_id', $event)"
    />

    <FilterField
      label="Escopo"
      type="select"
      :model-value="filters.somente_meus ? '1' : ''"
      :options="escopoOptions"
      placeholder="Toda a equipe"
      @update:model-value="updateFilter('somente_meus', $event)"
    />

    <div class="flex items-end justify-end pt-1 md:col-span-2 lg:col-span-3">
      <FilterActions @search="handleSearch" @clear="handleClear" />
    </div>
  </FilterSection>
</template>
