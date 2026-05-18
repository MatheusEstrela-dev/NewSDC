<template>
  <Head :title="`TDAP — Ata ${a.numero}`" />
  <div class="p-6 space-y-6">
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
                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">Município</th>
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
                <td class="px-4 py-3">{{ l.municipio_nome }}<span v-if="l.municipio_uf">/{{ l.municipio_uf }}</span></td>
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
          <div class="mt-2 flex flex-wrap gap-2">
            <span v-if="a.vigente" class="bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium">Vigente</span>
            <span v-if="a.ativo" class="bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium">Ativa</span>
            <span v-if="!a.ativo" class="bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400 inline-flex items-center px-2.5 py-1 rounded-full text-sm font-medium">Inativa</span>
          </div>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import TdapLayout from '@/Layouts/TdapLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import CalendarIcon from '@/Components/Icons/CalendarIcon.vue';

defineOptions({ layout: TdapLayout });

const props = defineProps({
  ata:       { type: Object, required: true },
  canEdit:   { type: Boolean, default: false },
  canDelete: { type: Boolean, default: false },
  canLote:   { type: Boolean, default: false },
});

const a = computed(() => props.ata.data ?? props.ata).value;

function excluir() {
  if (!confirm(`Excluir a ata ${a.numero}?`)) return;
  router.delete(route('tdap.atas.destroy', a.id));
}

function formatDate(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleDateString('pt-BR');
}
</script>
