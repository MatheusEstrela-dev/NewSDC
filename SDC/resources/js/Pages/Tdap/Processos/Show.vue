<template>
  <Head :title="`TDAP — Processo ${p.numero}`" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      :title="`Processo ${p.numero}`"
      :description="p.municipio?.nome ? `${p.municipio.nome}${p.municipio.uf ? '/' + p.municipio.uf : ''}` : ''"
      :icon="TruckIcon"
    >
      <template #actions>
        <Link :href="route('tdap.processos.swimlanes')">
          <SecondaryButton>Swimlanes</SecondaryButton>
        </Link>
        <Link :href="route('tdap.processos.index')">
          <SecondaryButton>Lista</SecondaryButton>
        </Link>
      </template>
    </TdapPageHeader>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
      <div class="lg:col-span-2 space-y-4">
        <div class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Identificação</h3>
          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-slate-500">Número</dt><dd class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ p.numero }}</dd></div>
            <div><dt class="text-slate-500">Estado</dt><dd><EstadoProcessoBadge :estado="p.estado" /></dd></div>
            <div><dt class="text-slate-500">Swimlane atual</dt><dd>{{ p.swimlane_label }}</dd></div>
            <div><dt class="text-slate-500">Município</dt><dd>{{ p.municipio?.nome }}<span v-if="p.municipio?.uf">/{{ p.municipio.uf }}</span></dd></div>
            <div><dt class="text-slate-500">Aberto em</dt><dd>{{ fmtDateTime(p.aberto_em) }}</dd></div>
            <div v-if="p.aberto_por"><dt class="text-slate-500">Aberto por</dt><dd>{{ p.aberto_por.name }}</dd></div>
            <div v-if="p.encerrado_em"><dt class="text-slate-500">Encerrado em</dt><dd>{{ fmtDateTime(p.encerrado_em) }}</dd></div>
            <div v-if="p.decretacao_id"><dt class="text-slate-500">Decretação vinculada</dt><dd>#{{ p.decretacao_id }}</dd></div>
            <div v-if="p.pae_form_id"><dt class="text-slate-500">PAE Form vinculado</dt><dd>#{{ p.pae_form_id }}</dd></div>
          </dl>
        </div>

        <div v-if="p.contexto" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Contexto</h3>
          <pre class="text-xs bg-slate-900 text-slate-100 rounded p-4 overflow-x-auto">{{ JSON.stringify(p.contexto, null, 2) }}</pre>
        </div>
      </div>

      <aside class="space-y-4">
        <div v-if="!p.eh_terminal && canTransitar" class="bg-white dark:bg-slate-900/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Transitar para</h3>
          <p v-if="p.transicoes_permitidas.length === 0" class="text-sm text-slate-400">Sem transições disponíveis.</p>
          <div v-else class="space-y-2">
            <button
              v-for="t in p.transicoes_permitidas"
              :key="t.value"
              @click="transitar(t.value, t.label)"
              class="w-full text-left px-3 py-2 rounded border border-slate-200 dark:border-slate-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 text-sm font-medium text-slate-700 dark:text-slate-300"
            >
              → {{ t.label }}
            </button>
          </div>
        </div>
        <div v-else-if="p.eh_terminal" class="bg-slate-100 dark:bg-slate-800/40 rounded-xl p-6 border border-slate-200 dark:border-slate-700/40 text-center">
          <p class="text-sm text-slate-500">Processo encerrado (estado terminal).</p>
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
import EstadoProcessoBadge from '@/Components/Organisms/Tdap/EstadoProcessoBadge.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

const props = defineProps({
  processo: { type: Object, required: true },
  canTransitar: { type: Boolean, default: false },
});

const p = computed(() => props.processo.data ?? props.processo).value;

function transitar(estadoAlvo, label) {
  const motivo = prompt(`Motivo da transição para "${label}" (opcional):`) ?? '';
  router.post(route('tdap.processos.transitar', p.id), {
    estado_alvo: estadoAlvo,
    motivo,
  }, { preserveScroll: true });
}

function fmtDateTime(d) {
  if (!d) return '—';
  return new Date(d).toLocaleString('pt-BR');
}
</script>
