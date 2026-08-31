<script setup>
/**
 * Situacao do turno como pill colorida.
 *
 * Substitui o `getStatusClasses()` que a tabela e a grade tinham copiado, e que
 * estava QUEBRADO: ele comparava com a string 'ativo', mas o DTO manda o LABEL
 * ("Pendente de aceite", "Finalizado com divergencia"). Nenhum label casava,
 * entao todo status que nao fosse exatamente "Ativo" caia no cinza -- inclusive
 * a divergencia, que e justamente a que precisa saltar aos olhos.
 *
 * A correcao de fundo e comparar pelo VALOR do enum, nao pelo texto exibido:
 * label e para o humano ler e pode mudar; valor e contrato.
 *
 * As cores dizem o que fazer:
 *   ATIVO                       verde   - em andamento agora
 *   PENDENTE_ACEITE             ambar   - alguem precisa conferir e aceitar
 *   FINALIZADO                  azul    - fechado, nada a fazer
 *   FINALIZADO_COM_DIVERGENCIA  vermelho- fechado com pendencia apontada
 */
import Badge from '@/Components/Atoms/Badge/Badge.vue';
import { computed } from 'vue';

const props = defineProps({
  // StatusPlantao cru, vindo de `status_valor`.
  status: {
    type: String,
    default: null,
  },
  label: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'sm',
  },
});

const variant = computed(() => {
  switch (props.status) {
    case 'ATIVO':
      return 'success';
    case 'PENDENTE_ACEITE':
      return 'warning';
    case 'FINALIZADO_COM_DIVERGENCIA':
      return 'danger';
    case 'FINALIZADO':
      return 'info';
    default:
      return 'neutral';
  }
});
</script>

<template>
  <Badge v-if="label || status" :variant="variant" :size="size">
    {{ label || status }}
  </Badge>
</template>
