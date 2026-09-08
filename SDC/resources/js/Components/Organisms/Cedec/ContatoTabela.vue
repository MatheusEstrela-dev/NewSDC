<script setup>
/**
 * Tabela de contatos: tabela no desktop, cartoes empilhados abaixo de lg.
 *
 * Generico por colunas para servir as duas abas -- e-mails e telefones -- sem
 * duplicar markup.
 *
 * O corte e por isDesktop (lg, 1024px) e nao por isMobile (md, 767px): a regra do
 * projeto manda alinhar toda decisao de layout em lg. Com o corte em md, a tabela de
 * seis colunas seguiria rolando de lado entre 768 e 1023px.
 */
import ListEmptyState from '@/Components/Molecules/ListEmptyState.vue';
import { useMobile } from '@/Composables/useMobile';

defineProps({
  /** [{ key: 'email_prefeitura', label: 'E-mail institucional' }] */
  colunas: { type: Array, required: true },
  linhas: { type: Array, default: () => [] },
  /** Chave usada como titulo do cartao no mobile e como primeira coluna. */
  campoTitulo: { type: String, default: 'municipio_nome' },
  vazioTitulo: { type: String, default: 'Nenhum contato encontrado' },
  vazioAjuda: { type: String, default: 'Nenhuma prefeitura tem contato cadastrado.' },
});

const { isDesktop } = useMobile();
</script>

<template>
  <ListEmptyState
    v-if="linhas.length === 0"
    :title="vazioTitulo"
    :helper="vazioAjuda"
  />

  <!-- Desktop: tabela. min-w-0 mais overflow-x-auto contem o transbordo NELA, nao na pagina. -->
  <div v-else-if="isDesktop" class="min-w-0 overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-700/50">
    <table class="w-full text-left text-sm">
      <thead class="bg-slate-50 dark:bg-slate-800/60">
        <tr>
          <th class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">Município</th>
          <th
            v-for="coluna in colunas"
            :key="coluna.key"
            class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200"
          >
            {{ coluna.label }}
          </th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 dark:divide-slate-700/50">
        <tr
          v-for="linha in linhas"
          :key="linha.municipio_id"
          class="bg-white dark:bg-slate-900/40"
        >
          <td class="px-4 py-2 font-medium text-slate-900 dark:text-slate-100">
            {{ linha[campoTitulo] }}
          </td>
          <td
            v-for="coluna in colunas"
            :key="coluna.key"
            class="px-4 py-2 text-slate-600 break-words [overflow-wrap:anywhere] dark:text-slate-300"
          >
            {{ linha[coluna.key] || '—' }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- Abaixo de lg: um cartao por municipio, pares rotulo/valor -->
  <div v-else class="space-y-3">
    <div
      v-for="linha in linhas"
      :key="linha.municipio_id"
      class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-700/50 dark:bg-slate-900/40"
    >
      <p class="text-sm font-bold text-slate-900 break-words dark:text-slate-100">
        {{ linha[campoTitulo] }}
      </p>
      <dl class="mt-2 grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
        <div v-for="coluna in colunas" :key="coluna.key" class="min-w-0">
          <dt class="text-xs text-slate-500 dark:text-slate-400">{{ coluna.label }}</dt>
          <dd class="text-sm text-slate-700 break-words [overflow-wrap:anywhere] dark:text-slate-200">
            {{ linha[coluna.key] || '—' }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
