<template>
  <FilterSection title="Filtros" :columns="5">
    <FilterField v-model="filtros.nome" label="Nome" placeholder="Nome" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.cpf" label="CPF" placeholder="11 dígitos" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.setor" label="Setor" placeholder="Setor" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.status" label="Status" type="select" :options="OPCOES_STATUS" />
    <div class="flex items-end gap-2">
      <Button variant="primary" size="md" @click="$emit('aplicar')">Buscar</Button>
      <Button variant="secondary" size="md" @click="$emit('limpar')">Limpar</Button>
    </div>
  </FilterSection>
</template>

<script setup>
import FilterSection from '@/Components/Molecules/Filter/FilterSection.vue';
import FilterField from '@/Components/Molecules/Filter/FilterField.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { STATUS_ACESSO } from '@/Support/acessos';

defineProps({
  filtros: { type: Object, required: true },
});
defineEmits(['aplicar', 'limpar']);

const OPCOES_STATUS = [{ value: '', label: 'Todos' }, ...STATUS_ACESSO.map(({ value, label }) => ({ value, label }))];
</script>
