<template>
  <Head :title="`TDAP — Evento #${h.id}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="`Evento #${h.id}`"
      :description="h.tipo_evento"
      :icon="ClockIcon"
    >
    </TdapPageHeader>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div><dt class="font-medium text-slate-500 dark:text-slate-400">Data do evento</dt><dd>{{ fmtDateTime(h.data_evento) }}</dd></div>
        <div><dt class="font-medium text-slate-500 dark:text-slate-400">Tipo</dt><dd class="">{{ h.tipo_evento }}</dd></div>
        <div><dt class="font-medium text-slate-500 dark:text-slate-400">Entidade</dt><dd class="">{{ h.entity_type }}#{{ h.entity_id }}</dd></div>
        <div><dt class="font-medium text-slate-500 dark:text-slate-400">Usuário</dt><dd>{{ h.user?.name || 'Sistema' }}</dd></div>
        <div class="md:col-span-2"><dt class="font-medium text-slate-500 dark:text-slate-400">Mensagem</dt><dd class="whitespace-pre-line">{{ h.obs || '—' }}</dd></div>
      </dl>
    </div>

    <div v-if="h.payload" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
      <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100 mb-3">Payload</h3>
      <pre class="text-xs bg-slate-900 text-slate-100 rounded p-4 overflow-x-auto">{{ JSON.stringify(h.payload, null, 2) }}</pre>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  historico: { type: Object, required: true },
});

const h = computed(() => props.historico.data ?? props.historico).value;

function fmtDateTime(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('pt-BR');
}
</script>
