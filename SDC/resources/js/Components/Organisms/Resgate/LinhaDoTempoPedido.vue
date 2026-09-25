<template>
  <section class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" aria-labelledby="linha-tempo-titulo" data-linha-tempo>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h2 id="linha-tempo-titulo" class="text-base font-bold text-slate-900 dark:text-white">Trilha do pedido</h2>
      <span class="rounded-full px-3 py-1 text-xs font-bold" :class="adulteradoEm === null ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-300'" data-integridade>
        {{ adulteradoEm === null ? 'Cadeia íntegra' : `Cadeia quebrada no evento ${adulteradoEm}` }}
      </span>
    </div>

    <ol class="mt-4 space-y-4 border-l-2 border-slate-200 pl-5 dark:border-slate-700">
      <li v-for="evento in eventos" :key="evento.sequencia" class="relative">
        <span class="absolute -left-[1.72rem] top-1 h-3 w-3 rounded-full" :class="COR[evento.etapa] ?? 'bg-slate-400'" aria-hidden="true" />
        <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ ETAPA[evento.etapa] ?? evento.etapa }}</p>
        <p class="text-xs text-slate-500 dark:text-slate-400">
          {{ dataHora(evento.ocorrido_em) }} ·
          {{ evento.ator_user_id ? (usuarios[evento.ator_user_id] ?? `#${evento.ator_user_id}`) : 'Sistema' }}
          <template v-if="evento.ip_address"> · IP {{ evento.ip_address }}</template>
          <template v-if="evento.permissao"> · {{ evento.permissao }}</template>
        </p>
        <p v-if="evento.justificativa" class="mt-1 text-xs text-slate-700 dark:text-slate-200">{{ evento.justificativa }}</p>
        <p class="mt-1 break-all font-mono text-[10px] text-slate-400 dark:text-slate-500" :title="`Hash anterior: ${evento.hash_anterior ?? 'nenhum'}`">#{{ evento.sequencia }} · {{ evento.hash }}</p>
      </li>
    </ol>
  </section>
</template>

<script setup>
/**
 * Trilha do pedido: cada passo com autor, IP, permissao usada e hash
 * encadeado. O selo de integridade vem do backend, que recalcula a cadeia
 * (HashEvento::verificar) a cada leitura.
 */
defineProps({
  eventos: { type: Array, default: () => [] },
  usuarios: { type: Object, default: () => ({}) },
  adulteradoEm: { type: Number, default: null },
});

const ETAPA = {
  solicitar: 'Solicitado · pontos e unidade reservados', aprovar: 'Aprovado pela CEDEC', recusar: 'Recusado pela CEDEC',
  cancelar: 'Cancelado pelo solicitante', expirar: 'Expirado por prazo', termo: 'Termo registrado (SEI)',
  assinar_estado: 'Assinado pelo Estado', assinar_municipio: 'Assinado pelo município', entregar: 'Entregue',
  contestar: 'Entrega contestada', confirmar: 'Recebimento confirmado · pontos debitados',
};
const COR = {
  solicitar: 'bg-amber-400', aprovar: 'bg-sky-500', recusar: 'bg-red-500', cancelar: 'bg-slate-400', expirar: 'bg-slate-400',
  termo: 'bg-sky-500', assinar_estado: 'bg-violet-500', assinar_municipio: 'bg-violet-500', entregar: 'bg-violet-500',
  contestar: 'bg-orange-500', confirmar: 'bg-emerald-500',
};
const dataHora = (valor) => (valor ? new Intl.DateTimeFormat('pt-BR', { dateStyle: 'short', timeStyle: 'medium' }).format(new Date(valor.replace(' ', 'T').replace(/\+00$/, 'Z'))) : '');
</script>
