<template>
  <div class="border-t border-slate-200 pt-3 dark:border-slate-700/50">
    <p
      v-if="lidaEm"
      class="flex items-center gap-2 text-sm font-medium text-emerald-700 dark:text-emerald-400"
    >
      <CheckCircleIcon class="h-5 w-5 shrink-0" />
      Ciente em {{ dataHora(lidaEm) }}
    </p>

    <template v-else-if="podeDarCiencia">
      <Button
        variant="success"
        size="sm"
        full-width
        :icon="CheckCircleIcon"
        :loading="processando"
        :disabled="processando"
        @click="$emit('confirmar')"
      >
        Li e estou ciente
      </Button>
      <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
        Registra que você leu esta versão do procedimento.
      </p>
    </template>

    <p v-else class="text-xs text-slate-500 dark:text-slate-400">{{ motivo }}</p>
  </div>
</template>

<script setup>
/**
 * Confirmacao de leitura da versao aberta. Burra: recebe o estado e emite
 * `confirmar`; quem registra no servidor e a pagina.
 */
import { CheckCircleIcon } from '@heroicons/vue/24/outline';
import Button from '@/Components/Atoms/Button/Button.vue';

defineProps({
  lidaEm: { type: String, default: null },
  podeDarCiencia: { type: Boolean, default: false },
  /** Por que nao da para dar ciencia (versao anterior, nota nao publicada). */
  motivo: { type: String, default: '' },
  processando: { type: Boolean, default: false },
});

defineEmits(['confirmar']);

function dataHora(iso) {
  return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
