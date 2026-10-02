<template>
  <ListaResponsiva v-bind="$attrs">
    <template v-for="(_, nome) in $slots" #[nome]="escopo">
      <slot :name="nome" v-bind="escopo ?? {}" />
    </template>
  </ListaResponsiva>
</template>

<script setup>
import ListaResponsiva from '@/Components/Molecules/List/ListaResponsiva.vue';

/**
 * Caminho antigo da casca, mantido para quem ainda importa daqui.
 * Repassa props e slots para Molecules/List/ListaResponsiva.vue;
 * codigo novo deve importar direto de la.
 */
defineOptions({ inheritAttrs: false });
</script>
