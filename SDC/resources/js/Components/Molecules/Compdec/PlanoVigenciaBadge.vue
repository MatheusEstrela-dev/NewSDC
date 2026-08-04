<template>
  <div class="inline-flex gap-1 items-center">
    <Badge :variant="ativo ? 'success' : 'default'" size="pill" class="whitespace-nowrap">
      {{ ativo ? 'Ativo' : 'Inativo' }}
    </Badge>

    <!-- Aprovacao e um selo adicional, nao um estado alternativo: aparece ao lado
         do ativo/inativo, e nao no lugar dele. Azul (info) porque nao expressa
         progresso nem impedimento, apenas um fato registrado. -->
    <Badge v-if="estaAprovado" cor="blue" size="pill" class="whitespace-nowrap">
      Aprovado
    </Badge>
  </div>
</template>

<script setup>
/**
 * Vigencia do plano de contingencia da COMPDEC.
 *
 * A receita de pill vinha escrita a mao aqui, em duas variaveis de classe.
 */
import Badge from '../../Atoms/Badge/Badge.vue';

defineProps({
  ativo: { type: Boolean, default: false },
  estaAprovado: { type: Boolean, default: false },
});
</script>
