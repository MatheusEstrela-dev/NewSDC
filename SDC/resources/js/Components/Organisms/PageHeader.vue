<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import { computed } from 'vue';

const props = defineProps({
  /**
   * Título principal da página
   */
  title: {
    type: String,
    required: true,
  },
  /**
   * Descrição/subtítulo da página
   */
  description: {
    type: String,
    default: '',
  },
  /**
   * Componente de ícone Vue a ser exibido
   */
  icon: {
    type: [Object, Function],
    default: null,
  },
  /**
   * URL de uma imagem (SVG/PNG) para o ícone do módulo — tem prioridade sobre `icon`.
   */
  iconImage: {
    type: String,
    default: '',
  },
  /**
   * Variante do header
   * - default: fundo simples
   * - gradient: fundo com gradiente
   */
  variant: {
    type: String,
    default: 'default',
    validator: (value) => ['default', 'gradient'].includes(value),
  },
  /**
   * Classes extras para o ícone
   */
  iconClass: {
    type: String,
    default: '',
  },
  /**
   * Espaco inferior. Mesma semantica do StatCardsGrid: a maioria das paginas
   * deixa o header controlar a propria margem, mas quem envolve os blocos num
   * container `space-y-*` precisa desligar isso -- senao as duas margens somam e
   * o primeiro gap fica no dobro dos outros.
   */
  espacoInferior: {
    type: Boolean,
    default: true,
  },
});

const containerClasses = computed(() => {
  const base = props.espacoInferior ? 'mb-6' : '';
  
  if (props.variant === 'gradient') {
    return `${base} rounded-2xl p-6 border bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30 border-slate-200 dark:border-slate-700/30`;
  }
  
  return base;
});

const iconContainerClasses = computed(() => {
  // Icone-imagem do modulo: maior e sem moldura (a arte colorida fala por si).
  if (props.iconImage) {
    return 'w-16 h-16 flex items-center justify-center shrink-0';
  }
  if (props.variant === 'gradient') {
    return 'w-11 h-11 rounded-full bg-transparent flex items-center justify-center border border-slate-300 dark:border-slate-600/40';
  }
  return 'w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center';
});
</script>

<template>
  <div :class="containerClasses">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <!-- Ícone opcional: imagem do módulo (SVG/PNG) tem prioridade sobre o componente -->
        <div v-if="iconImage || icon" :class="iconContainerClasses">
          <img
            v-if="iconImage"
            :src="iconImage"
            :alt="title"
            class="h-14 w-14 object-contain"
          />
          <component
            v-else
            :is="icon"
            :class="['w-6 h-6', iconClass || 'text-slate-600 dark:text-slate-200']"
          />
        </div>
        
        <!-- Título e descrição -->
        <div>
          <Heading :level="2" class="text-slate-900 dark:text-slate-100 mb-1">
            {{ title }}
          </Heading>
          <Text v-if="description" size="sm" color="muted">
            {{ description }}
          </Text>
        </div>
      </div>

      <!--
        Slot para ações (botões).

        flex-wrap e obrigatorio aqui: modulos como PAE, RAT e Ajuda Humanitaria tem
        4-5 botoes no header e, sem quebra de linha, o ultimo era cortado na faixa
        entre md e a largura necessaria para todos. O pai ja e flex-col md:flex-row,
        entao no mobile as acoes descem para baixo do titulo.
      -->
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:gap-3 md:justify-end">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
