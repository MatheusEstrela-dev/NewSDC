<template>
  <FilterSection title="Filtros" :columns="3">
    <FilterField v-model="filtros.search" label="Busca" placeholder="Nome ou ponto de rede" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.status" label="Ocupação" type="select" :options="OPCOES_STATUS" />
    <div class="flex items-end gap-2">
      <Button variant="primary" size="md" @click="$emit('aplicar')">Filtrar</Button>
      <Button variant="secondary" size="md" @click="$emit('limpar')">Limpar</Button>
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import Button from '@/Components/Atoms/Button/Button.vue';

defineProps({
  filtros: { type: Object, required: true },
});
defineEmits(['aplicar', 'limpar']);

// Mesmos valores aceitos pelo EstacaoController (livre/ocupada).
const OPCOES_STATUS = [
  { value: '', label: 'Todas' },
  { value: 'livre', label: 'Livre' },
  { value: 'ocupada', label: 'Ocupada' },
];
</script>
