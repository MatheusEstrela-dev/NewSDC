<template>
  <!--
    Sem `mb-6` proprio: toda pagina do TDAP envolve os blocos num `space-y-6`, e
    as duas margens somavam -- o primeiro intervalo ficava em 48px contra os 24px
    do resto do sistema. Mesma razao pela qual PageHeader e StatCardsGrid tem o
    `espacoInferior`.
  -->
  <div class="rounded-2xl p-6 border
              bg-gradient-to-r from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-700/30
              bg-white dark:bg-slate-900/25
              border-slate-200 dark:border-slate-700/30">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div
          v-if="iconImage"
          class="w-16 h-16 flex items-center justify-center shrink-0"
        >
          <img :src="iconImage" :alt="title" class="h-14 w-14 object-contain" />
        </div>
        <div
          v-else
          class="w-11 h-11 rounded-full bg-transparent flex items-center justify-center
                    border border-slate-300 dark:border-slate-600/40"
        >
          <component :is="icon" class="w-6 h-6 text-slate-600 dark:text-slate-200" />
        </div>
        <div>
          <Heading :level="2" class="mb-1">
            {{ title }}
          </Heading>
          <Text v-if="description" size="sm" color="muted">
            {{ description }}
          </Text>
        </div>
      </div>
      <!--
        flex-wrap e md:justify-end alinham com o slot de acoes do PageHeader: sem
        a quebra, paginas com 3+ botoes cortavam o ultimo entre md e a largura
        necessaria para todos.
      -->
      <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:gap-3 md:justify-end">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
import Heading from '@/Components/Atoms/Typography/Heading.vue';
import Text from '@/Components/Atoms/Typography/Text.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import { moduleIcon } from '@/Support/moduleIcons';

defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
  icon: { type: Object, default: () => TruckIcon },
  iconImage: { type: String, default: () => moduleIcon('tdap') },
});
</script>
