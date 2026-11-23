<script setup>
/** Capa do curso: o EnvioDeCapa com as rotas de capa do curso. */
import EnvioDeCapa from '@/Components/Molecules/Treinamento/EnvioDeCapa.vue';

defineProps({
  curso: { type: Object, required: true }, // { id, titulo, capa_url }
});
</script>

<template>
  <EnvioDeCapa
    :rotulo="curso.titulo || ''"
    :tem-capa="Boolean(curso.capa_url)"
    :url-enviar="route('treinamentos.professor.capa.store', curso.id)"
    :url-remover="route('treinamentos.professor.capa.destroy', curso.id)"
  />
</template>
