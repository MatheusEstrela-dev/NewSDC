<template>
  <div class="mb-4 flex flex-col gap-3 border-b border-slate-200 pb-3 dark:border-slate-700/50 sm:mb-6 sm:pb-4">
    <div class="flex items-start gap-3 sm:items-center">
      <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/20 sm:h-12 sm:w-12 sm:rounded-xl">
        <component :is="iconeEfetivo" class="h-5 w-5 text-white sm:h-6 sm:w-6" />
      </div>

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ title }}</h1>
          <Badge v-if="statusLabel" :variant="statusVariant" size="md">{{ statusLabel }}</Badge>
        </div>
        <p v-if="subtitle" class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ subtitle }}</p>

        <div v-if="contextValue" class="mt-2 flex items-center gap-2">
          <span v-if="contextLabel" class="whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ contextLabel }}</span>
          <span class="rounded border border-blue-200 bg-blue-50 px-2 py-0.5 font-mono text-base font-black tracking-widest text-blue-700 dark:border-blue-700/50 dark:bg-blue-900/20 dark:text-blue-300 sm:text-xl">{{ contextValue }}</span>
        </div>

        <slot name="extra" />
      </div>

      <div v-if="$slots.actions" class="flex flex-shrink-0 flex-wrap items-center gap-2">
        <slot name="actions" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { DocumentTextIcon } from '@heroicons/vue/24/outline';
import Badge from '@/Components/Atoms/Badge/Badge.vue';

/**
 * Cabecalho de pagina de detalhe: icone em degrade, titulo, selo de situacao e
 * chip de contexto (por exemplo o numero do protocolo). Espelha a primeira
 * linha do RatHeader sem os dados especificos do RAT.
 */
const props = defineProps({
  title: { type: String, required: true },
  // Sem default de funcao: em prop do tipo Function o Vue usa o default como o proprio valor.
  icon: { type: [Object, Function], default: undefined },
  subtitle: { type: String, default: '' },
  statusLabel: { type: String, default: '' },
  statusVariant: { type: String, default: 'default' },
  contextLabel: { type: String, default: '' },
  contextValue: { type: String, default: '' },
});

const iconeEfetivo = computed(() => props.icon ?? DocumentTextIcon);
</script>
