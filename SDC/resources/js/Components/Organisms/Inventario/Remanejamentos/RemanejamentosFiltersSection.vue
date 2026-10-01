<template>
  <FilterSection title="Filtros" :columns="4">
    <FilterField v-model="filtros.search" label="Busca" placeholder="Pessoa, equipamento ou patrimônio" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.status" label="Status" type="select" :options="[{ value: '', label: 'Todos' }, ...opcoesStatus]" />
    <FilterField v-model="filtros.data" label="Data" type="date" />
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
  opcoesStatus: { type: Array, required: true },
});
defineEmits(['aplicar', 'limpar']);
</script>
