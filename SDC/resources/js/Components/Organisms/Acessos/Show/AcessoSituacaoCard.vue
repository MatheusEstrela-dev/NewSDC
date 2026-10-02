<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700/50 dark:bg-slate-900/60">
    <h2 class="mb-4 text-lg font-bold text-slate-900 dark:text-slate-100">Situação</h2>
    <dl class="space-y-3 text-sm">
      <div><dt class="text-xs text-slate-500 dark:text-slate-400">Status</dt><dd class="mt-0.5"><StatusAcessoBadge :status="cadastro.status" /></dd></div>
      <div><dt class="text-xs text-slate-500 dark:text-slate-400">Status no AD</dt><dd class="break-words text-slate-900 dark:text-slate-100">{{ cadastro.status_ad || '—' }}</dd></div>
      <div><dt class="text-xs text-slate-500 dark:text-slate-400">Solicitação</dt><dd class="text-slate-900 dark:text-slate-100">{{ formatarDataHora(cadastro.data_solicitacao) }}</dd></div>
      <div><dt class="text-xs text-slate-500 dark:text-slate-400">Aprovação</dt><dd class="text-slate-900 dark:text-slate-100">{{ formatarDataHora(cadastro.data_aprovacao) }}</dd></div>
    </dl>
  </section>
</template>

<script setup>
import StatusAcessoBadge from '@/Components/Atoms/Acessos/StatusAcessoBadge.vue';
import { formatarDataHora } from '@/utils/dateFormatter';

defineProps({
  cadastro: { type: Object, required: true },
});
</script>
