<script setup>
import { CheckBadgeIcon } from '@heroicons/vue/24/solid';
import { formatDate } from '@/Utils/dateFormatter';

/* Certificado valido de um servidor no resultado da leitura de QR. */
defineProps({
  certificado: { type: Object, required: true },
});
</script>

<template>
  <li class="flex items-start gap-3 py-3">
    <CheckBadgeIcon class="mt-0.5 h-6 w-6 shrink-0 text-emerald-500" />
    <div class="min-w-0">
      <p class="break-words text-base font-semibold text-slate-900 dark:text-white">{{ certificado.titulo }}</p>
      <p class="text-sm text-slate-600 dark:text-slate-300">
        Emitido em {{ formatDate(certificado.emitido_em) || '-' }}
      </p>
      <p class="break-all font-mono text-xs text-slate-500 dark:text-slate-400">
        Código {{ certificado.codigo_verificacao }}
      </p>
    </div>
  </li>
</template>
