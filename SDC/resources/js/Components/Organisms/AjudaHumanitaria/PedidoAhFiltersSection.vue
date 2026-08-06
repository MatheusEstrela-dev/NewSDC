<template>
  <FilterSection title="Filtros de Pesquisa" :columns="4" class="mb-6">
    <FilterField
      label="Buscar"
      type="search"
      :model-value="filtros.search || ''"
      placeholder="Número, decreto ou município"
      @update:model-value="atualizar('search', $event)"
    />

    <FilterField
      label="Status"
      type="select"
      :model-value="filtros.status ?? ''"
      :options="opcoesStatusNormalizadas"
      placeholder="Todos"
      @update:model-value="atualizar('status', $event)"
    />

    <FilterField
      label="Ano"
      type="select"
      :model-value="filtros.ano || ''"
      :options="opcoesAno"
      placeholder="Todos"
      @update:model-value="atualizar('ano', $event)"
    />

    <div class="flex items-end justify-end pt-1">
      <FilterActions @search="$emit('aplicar')" @clear="$emit('limpar')" />
    </div>
  </FilterSection>
</template>

<script setup>
import { computed } from 'vue';
import FilterActions from '@/Components/Molecules/Filter/FilterActions.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';

const props = defineProps({
  filtros: { type: Object, default: () => ({}) },
  opcoesStatus: { type: Array, default: () => [] },
});

const emit = defineEmits(['filtro-alterado', 'aplicar', 'limpar']);

/**
 * StatusPedidoAh::options() devolve value, label e fase. O FilterField espera
 * apenas value e label.
 */
const opcoesStatusNormalizadas = computed(() =>
  props.opcoesStatus.map((opcao) => ({ value: opcao.value, label: opcao.label })),
);

const opcoesAno = computed(() => {
  const atual = new Date().getFullYear();

  return Array.from({ length: 6 }, (_, i) => {
    const ano = atual - i;

    return { value: ano, label: String(ano) };
  });
});

function atualizar(campo, valor) {
  emit('filtro-alterado', { campo, valor });
}
</script>
