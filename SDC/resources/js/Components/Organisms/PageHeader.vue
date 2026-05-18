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
});

const containerClasses = computed(() => {
  const base = 'mb-6 min-w-0';
  
  if (props.variant === 'gradient') {
    return `${base} rounded-xl sm:rounded-2xl p-4 sm:p-5 lg:p-6 border bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30 border-slate-200 dark:border-slate-700/30`;
  }
  
  return base;
});

const iconContainerClasses = computed(() => {
  if (props.variant === 'gradient') {
    return 'w-11 h-11 rounded-full bg-transparent flex items-center justify-center border border-slate-300 dark:border-slate-600/40';
  }
  return 'w-10 h-10 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center';
});
</script>

<template>
  <div :class="containerClasses">
    <div class="flex min-w-0 flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <!-- Ícone opcional -->
        <div v-if="icon" :class="[iconContainerClasses, 'shrink-0']">
          <component 
            :is="icon" 
            :class="['w-6 h-6', iconClass || 'text-slate-600 dark:text-slate-200']" 
          />
        </div>
        
        <!-- Título e descrição -->
        <div class="min-w-0">
          <Heading :level="2" class="mb-1 break-words text-slate-900 dark:text-slate-100">
            {{ title }}
          </Heading>
          <Text v-if="description" size="sm" color="muted" class="break-words">
            {{ description }}
          </Text>
        </div>
      </div>

      <!-- Slot para ações (botões) -->
      <div v-if="$slots.actions" class="flex w-full min-w-0 flex-wrap items-center justify-start gap-2 sm:gap-3 md:w-auto md:justify-end">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>
