<template>
  <Head :title="`TDAP — Ata ${a.numero}`" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      :title="`Ata ${a.numero}`"
      :description="`${formatDate(a.dt_inicio)} — ${formatDate(a.dt_final)}`"
      :icon="CalendarIcon"
    >
      <template #actions>
        <Link :href="route('tdap.atas.index')">
          <SecondaryButton>Voltar</SecondaryButton>
        </Link>
        <Link v-if="canEdit" :href="route('tdap.atas.edit', a.id)">
          <PrimaryButton>Editar</PrimaryButton>
        </Link>
        <Link v-if="canLote" :href="route('tdap.lotes.create', { ata_id: a.id })">
          <PrimaryButton>Adicionar Lote</PrimaryButton>
        </Link>
        <DangerButton v-if="canDelete && (a.lotes_count ?? 0) === 0" @click="excluir">
          Excluir
        </DangerButton>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Dados da Ata</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Número</dt><dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ a.numero }}</dd></div>
            <div><dt class="text-slate-500">Vigência</dt><dd class="text-slate-900 dark:text-slate-100">{{ formatDate(a.dt_inicio) }} — {{ formatDate(a.dt_final) }}</dd></div>
          </dl>
        </div>

        <div v-if="a.historico" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Histórico</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ a.historico }}</p>
        </div>

        <div v-if="a.observacoes" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Observações</h3>
          <p class="text-sm text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ a.observacoes }}</p>
        </div>

        <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
          <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Lotes ({{ a.lotes_count ?? 0 }})</h3>
            <Link v-if="canLote" :href="route('tdap.lotes.create', { ata_id: a.id })">
              <PrimaryButton size="sm">Adicionar</PrimaryButton>
            </Link>
          </div>
          <table v-if="a.lotes && a.lotes.length > 0" class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/40">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Lote</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Municípios</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Prestador</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">m³</th>
                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">R$/m³</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
              <tr v-for="l in a.lotes" :key="l.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
                <td class="px-4 py-3 font-mono">
                  <Link :href="route('tdap.lotes.show', l.id)" class="text-blue-600 hover:text-blue-800">{{ l.numero }}</Link>
                </td>
                <!--
                  O lote atende varios municipios: o join de todos os nomes
                  esticava a celula (ha lote com mais de 30). Mostra os
                  primeiros, o resto no contador e a lista inteira no title.
                -->
                <td class="px-4 py-3 align-top">
                  <div v-if="municipiosDo(l).length" class="max-w-xs" :title="listaMunicipios(l)">
                    <span class="text-slate-700 dark:text-slate-300">
                      {{ municipiosDo(l).slice(0, 3).map(m => m.nome).join(', ') }}
                    </span>
                    <span v-if="municipiosDo(l).length > 3" class="text-slate-400">
                      +{{ municipiosDo(l).length - 3 }}
                    </span>
                    <span class="block text-xs text-slate-400">{{ municipiosDo(l).length }} município(s)</span>
                  </div>
                  <span v-else class="text-slate-400">—</span>
                </td>
                <td class="px-4 py-3">{{ l.prestador_nome }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(l.qtd_agua_m3).toFixed(2) }}</td>
                <td class="px-4 py-3 text-right font-mono">{{ Number(l.valor_m3).toFixed(2) }}</td>
              </tr>
            </tbody>
          </table>
          <p v-else class="px-6 py-8 text-center text-slate-400">Nenhum lote cadastrado.</p>
        </div>
      </div>

      <aside class="space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <p class="text-sm text-slate-500">Status</p>
          <!--
            Situacao unica vinda do backend (AtaResource.situacao). Antes eram
            tres v-if independentes, que exibiam "Ativa" numa ata ja vencida e
            nunca exibiam "Vencida".
          -->
          <div class="mt-2 flex flex-wrap items-center gap-2">
            <span
              class="inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium"
              :class="classesSituacao"
            >
              {{ a.situacao_label ?? (a.ativo ? 'Ativa' : 'Inativa') }}
            </span>
            <span v-if="a.ativo" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium">Ativa</span>
          </div>
          <p v-if="a.dias_restantes !== null && a.dias_restantes !== undefined" class="mt-3 text-sm" :class="a.dias_restantes < 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-600 dark:text-slate-300'">
            {{ textoVigencia }}
          </p>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  ata:       { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canLote:   { type: Boolean, default: false },
});

const a = computed(() => props.ata.data ?? props.ata).value;

// Mapa token -> classes Tailwind (escrito por extenso: classe montada em string
// dinamica seria removida pelo purge do build). Espelha SituacaoAta::cor().
const classesBadge = {
  success: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
  danger:  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
  info:    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
  neutral: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
};

const classesSituacao = classesBadge[a.situacao_cor] ?? classesBadge.neutral;

// dias_restantes e assinado: negativo = ja venceu, 0 = vence hoje.
const textoVigencia = (() => {
  const dias = a.dias_restantes;
  if (dias === null || dias === undefined) return '';
  if (dias < 0) return `Vigência encerrada há ${Math.abs(dias)} dia(s).`;
  if (dias === 0) return 'A vigência termina hoje.';
  return `Faltam ${dias} dia(s) para o fim da vigência.`;
})();

function excluir() {
  if (!confirm(`Excluir a ata ${a.numero}?`)) return;
  router.delete(route('tdap.atas.destroy', a.id));
}

// Municipios do lote (relacao N:N); o fallback evita quebrar a tabela quando o
// payload chega sem a relacao carregada.
function municipiosDo(lote) {
  return Array.isArray(lote?.municipios) ? lote.municipios : [];
}

function listaMunicipios(lote) {
  return municipiosDo(lote)
    .map(m => (m.uf ? `${m.nome}/${m.uf}` : m.nome))
    .join(', ');
}

// Datas vem como 'YYYY-MM-DD'. `new Date('2026-05-01')` e meia-noite UTC e, no
// fuso do Brasil, exibia o dia anterior.
function formatDate(d) {
  if (!d) return '—';
  const [ano, mes, dia] = String(d).slice(0, 10).split('-');

  return ano && mes && dia ? `${dia}/${mes}/${ano}` : '—';
}
</script>
