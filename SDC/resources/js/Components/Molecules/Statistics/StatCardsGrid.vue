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
 * Ate 4 colunas em lg: com 5 cards nessa largura, comprimir a fileira inteira corta
 * o conteudo em notebook, entao o quinto quebra para a segunda linha.
 *
 * A opcao 5 existe para modulos com exatamente 5 cards (Decretacoes) e aplica a
 * quinta coluna somente em xl+: acima de 1280px cabe a fileira inteira sem
 * comprimir, e abaixo disso o comportamento continua sendo o de 4 colunas. Assim
 * a fileira unica volta no desktop sem reintroduzir os cards cortados em telas
 * menores, que foi o motivo do limite original.
 *
 * Recolher detalhe NAO e responsabilidade deste componente: o retrator vive
 * dentro do card (StatCardWithBreakdown), porque o que se recolhe e o detalhe de
 * cada card, nao a fileira inteira. Um retrator aqui esconderia tambem titulo e
 * numero, que sao justamente o que precisa ficar visivel.
 */
import { computed } from 'vue';

const props = defineProps({
  /**
   * Colunas no desktop. Aceita 2, 3, 4 ou 5.
   * Mobile e sempre 2 colunas, que e o que caber sem truncar o valor do card.
   * O valor 5 vale a partir de xl (ver nota no topo); 2 a 4 valem a partir de lg.
   */
  colunas: {
    type: Number,
    default: 4,
    validator: (v) => [2, 3, 4, 5].includes(v),
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
  // 4 em lg, 5 so em xl: preserva o limite de 4 onde ele foi criado (notebook)
  // e devolve a fileira unica onde ha largura para ela.
  5: 'lg:grid-cols-4 xl:grid-cols-5',
};

const classes = computed(() => [
  'grid grid-cols-2 md:grid-cols-2 gap-3 sm:gap-4',
  COLUNAS_DESKTOP[props.colunas],
  props.espacoInferior ? 'mb-6' : '',
]);
</script>
