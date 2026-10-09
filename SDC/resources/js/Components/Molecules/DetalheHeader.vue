<template>
  <div class="mb-4 flex min-w-0 flex-col gap-3 border-b border-slate-200 pb-3 dark:border-slate-700/50 sm:mb-6 sm:flex-row sm:items-center sm:justify-between sm:pb-4">
    <div class="flex min-w-0 flex-1 items-start gap-3 sm:items-center">
      <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg shadow-blue-500/20 sm:h-12 sm:w-12 sm:rounded-xl">
        <component :is="iconeEfetivo" aria-hidden="true" class="h-5 w-5 text-white sm:h-6 sm:w-6" />
      </div>

      <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
          <h1 class="break-words text-lg font-bold tracking-tight text-slate-900 dark:text-white sm:text-2xl">{{ title }}</h1>
          <Badge v-if="statusLabel" :variant="statusVariant" size="md">{{ statusLabel }}</Badge>
        </div>
        <p v-if="subtitle" class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ subtitle }}</p>

        <div v-if="contextValue" class="mt-2 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1">
          <span v-if="contextLabel" class="whitespace-nowrap text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ contextLabel }}</span>
          <span class="min-w-0 max-w-full break-all rounded border border-blue-200 bg-blue-50 px-2 py-0.5 font-mono text-sm font-black tracking-wide text-blue-700 dark:border-blue-700/50 dark:bg-blue-900/20 dark:text-blue-300 sm:text-xl sm:tracking-widest">{{ contextValue }}</span>
        </div>

        <slot name="extra" />
      </div>
    </div>

    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2 sm:flex-shrink-0 sm:justify-end">
      <slot name="actions" />
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
