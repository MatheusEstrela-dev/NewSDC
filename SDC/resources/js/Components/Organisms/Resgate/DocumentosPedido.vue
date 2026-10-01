<template>
  <section v-if="documentos.length" class="rounded-xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800" data-documentos-pedido>
    <h2 class="text-base font-bold text-slate-900 dark:text-white">Documentos</h2>
    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Cada arquivo é conferido pelo hash antes do download; arquivo alterado não é servido.</p>
    <ul class="mt-3 divide-y divide-slate-100 dark:divide-slate-700">
      <li v-for="doc in documentos" :key="doc.id" class="flex flex-wrap items-center justify-between gap-2 py-2">
        <div class="min-w-0">
          <a :href="route('resgate.pedidos.documento', { pedido: pedidoId, documento: doc.id })" class="text-sm font-semibold text-blue-700 hover:underline dark:text-blue-300">{{ doc.nome_original }}</a>
          <p class="text-xs text-slate-500 dark:text-slate-400">{{ TIPO[doc.tipo] ?? doc.tipo }} · {{ tamanho(doc.tamanho) }} · {{ usuarios[doc.enviado_por] ?? `#${doc.enviado_por}` }}</p>
        </div>
        <code class="max-w-full truncate text-[10px] text-slate-400 dark:text-slate-500" :title="doc.sha256">sha256 {{ doc.sha256.slice(0, 16) }}…</code>
      </li>
    </ul>
  </section>
</template>

<script setup>
/** Anexos do pedido com o hash de cada arquivo (download confere a integridade). */
defineProps({
  documentos: { type: Array, default: () => [] },
  pedidoId: { type: Number, required: true },
  usuarios: { type: Object, default: () => ({}) },
});

const TIPO = { termo: 'Termo', evidencia_entrega: 'Evidência de entrega', contestacao: 'Contestação' };
const tamanho = (bytes) => (bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`);
</script>
