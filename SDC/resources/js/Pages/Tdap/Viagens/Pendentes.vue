<template>
  <Head title="TDAP — Viagens Pendentes" />
  <div class="w-full space-y-6 pb-8">
    <TdapPageHeader
      title="Viagens pendentes de validação"
      description="Fila de viagens registradas aguardando aprovação"
      :icon="ClockIcon"
    >
      <template #actions>
        <Link :href="route('tdap.cronogramas.index')">
          <SecondaryButton>Voltar</SecondaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Cronograma</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Caminhão</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Data da viagem</th>
            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Observação</th>
            <th class="px-4 py-3 text-right text-xs font-semibold text-slate-700 dark:text-slate-200 uppercase tracking-wider">Ações</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
          <tr v-for="v in viagens.data" :key="v.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <td class="px-4 py-3 font-mono font-semibold">{{ v.cronograma_numero }}</td>
            <td class="px-4 py-3 font-mono">{{ v.caminhao_placa }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ fmtDateTime(v.data_registro) }}</td>
            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
              <span v-if="v.obs">{{ v.obs }}</span>
              <span v-else class="text-slate-400">—</span>
            </td>
            <td class="px-4 py-3 text-right space-x-2">
              <button v-if="canValidar" @click="validar(v, true)" class="px-3 py-1 text-xs font-medium rounded bg-emerald-600 text-white hover:bg-emerald-700">
                Aprovar
              </button>
              <button v-if="canValidar" @click="validar(v, false)" class="px-3 py-1 text-xs font-medium rounded bg-red-600 text-white hover:bg-red-700">
                Rejeitar
              </button>
            </td>
          </tr>
          <tr v-if="viagens.data.length === 0">
            <td colspan="5" class="px-4 py-12 text-center text-slate-400">
              <ClockIcon class="mx-auto h-10 w-10 text-slate-300 mb-2" />
              <p class="font-medium text-slate-900 dark:text-slate-100">Nenhuma viagem pendente</p>
              <p class="text-sm mt-1">Todas as viagens registradas foram validadas.</p>
            </td>
          </tr>
        </tbody>
      </table>

    </div>

      <Pagination :pagination="viagens.meta" @page-change="irParaPagina" />
  </div>
</template>

<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import Pagination from '@/Components/Molecules/Navigation/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  viagens:    { type: Object, default: () => ({ data: [], meta: {} }) },
  canValidar: { type: Boolean, default: false },
});

function validar(v, aprovada) {
  const obs = aprovada
    ? prompt('Observação de aprovação (opcional):') ?? ''
    : prompt('Motivo da rejeição (obrigatório):');

  if (!aprovada && !obs) {
    alert('Motivo da rejeição é obrigatório.');
    return;
  }

  router.post(route('tdap.viagens.validar', v.id), {
    aprovada,
    obs_aprovacao: obs,
  }, { preserveScroll: true });
}

function fmtDateTime(d) {
  if (!d) return '—';
  const date = typeof d === 'string' ? new Date(d) : d;
  return date.toLocaleString('pt-BR');
}

function irParaPagina(page) {
  router.get(route(route().current()), { page }, { preserveState: true, replace: true });
}
</script>
