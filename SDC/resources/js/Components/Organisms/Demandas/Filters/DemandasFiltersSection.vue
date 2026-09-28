<template>
  <FilterSection title="Filtros" :columns="4">
    <FilterField v-model="filtros.search" label="Busca" placeholder="Título, protocolo ou descrição" @keyup.enter="$emit('aplicar')" />
    <FilterField v-model="filtros.assunto_id" label="Assunto" type="select" :options="comTodos(opcoes.assuntos)" />
    <FilterField v-model="filtros.etapa" label="Status" type="select" :options="comTodos(opcoes.etapas)" />
    <FilterField v-model="filtros.prioridade" label="Prioridade" type="select" :options="comTodos(opcoes.prioridades)" />
    <template v-if="opcoes.usuarios.length">
      <FilterField v-model="filtros.solicitante_id" label="Solicitante" type="select" :options="comTodos(opcoes.usuarios)" />
      <FilterField v-model="filtros.responsavel_id" label="Responsável" type="select" :options="comTodos(opcoes.usuarios)" />
      <FilterField v-model="filtros.criado_por_id" label="Quem abriu" type="select" :options="comTodos(opcoes.usuarios)" />
    </template>
    <FilterField v-model="filtros.data_inicial" label="Data inicial" type="date" />
    <FilterField v-model="filtros.data_final" label="Data final" type="date" />
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
  opcoes: { type: Object, required: true },
});
defineEmits(['aplicar', 'limpar']);

const comTodos = (lista) => [{ value: '', label: 'Todos' }, ...lista];
</script>
