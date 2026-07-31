<template>
  <div :class="classes">
    <slot />
  </div>
</template>

<script setup>
/**
 * Grid responsivo dos stat cards dos modulos.
 *
 * A mesma string de grid estava copiada em 14 componentes de estatistica, e por isso
 * ja havia divergido: Decretacoes usava lg:grid-cols-5 (cards apertados e cortados em
 * notebook), Estoque e Inventario ficavam em 2 colunas ate no desktop. Centralizando
 * aqui, o padrao muda em um lugar e o proximo modulo nao volta a divergir.
 *
 * Referencia visual: o modulo PAE (grid-cols-2 / md:2 / lg:4).
 *
 * Maximo de 4 colunas por decisao de produto: com 5 cards, o quinto quebra para a
 * segunda linha em vez de comprimir a fileira inteira.
 */
import { computed } from 'vue';

const props = defineProps({
  /**
   * Colunas no desktop (lg+). Aceita 2, 3 ou 4.
   * Mobile e sempre 2 colunas, que e o que caber sem truncar o valor do card.
   */
  colunas: {
    type: Number,
    default: 4,
    validator: (v) => [2, 3, 4].includes(v),
  },

  /**
   * Espaco inferior. A maioria das listagens usa mb-6; Estoque, Inventario e TDAP
   * controlam a margem no proprio template e passam false.
   */
  espacoInferior: {
    type: Boolean,
    default: true,
  },
});

/**
 * Classes estaticas, nunca interpoladas: o Tailwind varre o codigo-fonte em busca de
 * literais, e `lg:grid-cols-${n}` nao seria detectado nem gerado no CSS final.
 */
const COLUNAS_DESKTOP = {
  2: 'lg:grid-cols-2',
  3: 'lg:grid-cols-3',
  4: 'lg:grid-cols-4',
};

const classes = computed(() => [
  'grid grid-cols-2 md:grid-cols-2 gap-3 sm:gap-4',
  COLUNAS_DESKTOP[props.colunas],
  props.espacoInferior ? 'mb-6' : '',
]);
</script>
