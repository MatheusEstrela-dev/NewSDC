<template>
  <Head title="TDAP — Dashboard" />
  <div class="p-6 space-y-6">
    <TdapPageHeader
      title="TDAP — Transporte e Distribuição de Água Potável"
      description="Gestão de cronogramas, prestadores, caminhões-tanque, vistorias e viagens"
      :icon="TruckIcon"
      :icon-image="moduleIcon('tdap')"
    />

    <!-- KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">Cronogramas ativos</p>
        <p class="text-2xl font-semibold text-emerald-600">{{ kpis.cronogramas_ativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">Encerrados</p>
        <p class="text-2xl font-semibold text-slate-400">{{ kpis.cronogramas_encerrados }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">Rascunhos</p>
        <p class="text-2xl font-semibold text-amber-600">{{ kpis.cronogramas_rascunhos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">m³ entregues (mês)</p>
        <p class="text-2xl font-semibold text-blue-600">{{ Number(kpis.m3_entregues_mes || 0).toLocaleString('pt-BR', {minimumFractionDigits:0,maximumFractionDigits:0}) }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">Prestadores ativos</p>
        <p class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ kpis.prestadores_ativos }}</p>
      </div>
      <div class="bg-white dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700/40">
        <p class="text-xs text-slate-500">Viagens p/ validar</p>
        <Link v-if="kpis.viagens_pendentes_validar > 0" :href="route('tdap.viagens.pendentes')" class="text-2xl font-semibold text-amber-600 hover:text-amber-700">
          {{ kpis.viagens_pendentes_validar }}
        </Link>
        <span v-else class="text-2xl font-semibold text-slate-400">0</span>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Cronogramas ativos -->
      <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Cronogramas ativos</h3>
          <Link :href="route('tdap.cronogramas.index')" class="text-xs text-blue-600 hover:text-blue-800">Ver todos →</Link>
        </div>
        <div v-if="cronogramasAtivos.length === 0" class="px-6 py-10 text-center text-slate-400">
          Nenhum cronograma ativo no momento.
        </div>
        <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
          <li v-for="c in cronogramasAtivos" :key="c.id" class="px-6 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/30">
            <Link :href="route('tdap.cronogramas.show', c.id)" class="block">
              <div class="flex items-center justify-between">
                <div>
                  <p class="font-mono font-semibold text-slate-900 dark:text-slate-100">{{ c.numero }}</p>
                  <p class="text-xs text-slate-500">{{ c.prestador_nome }} — {{ c.municipio_nome }}<span v-if="c.municipio_uf">/{{ c.municipio_uf }}</span></p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-slate-500">{{ fmtDate(c.dt_inicio) }} – {{ fmtDate(c.dt_final) }}</p>
                  <p class="text-xs text-slate-400">{{ c.caminhoes_count }} caminhão(ões)</p>
                </div>
              </div>
            </Link>
          </li>
        </ul>
      </div>

      <!-- Eventos recentes (Histórico) -->
      <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Eventos recentes</h3>
          <Link :href="route('tdap.historicos.index')" class="text-xs text-blue-600 hover:text-blue-800">Auditoria completa →</Link>
        </div>
        <div v-if="eventosRecentes.length === 0" class="px-6 py-10 text-center text-slate-400">
          Sem eventos registrados ainda.
        </div>
        <ul v-else class="divide-y divide-slate-200 dark:divide-slate-700">
          <li v-for="ev in eventosRecentes" :key="ev.id" class="px-6 py-3">
            <div class="flex items-start gap-3">
              <span :class="badgeTipo(ev.tipo_evento)" class="mt-0.5 px-2 py-0.5 rounded text-xs font-mono">
                {{ ev.tipo_evento }}
              </span>
              <div class="flex-1 min-w-0">
                <p class="text-sm text-slate-700 dark:text-slate-300 truncate">{{ ev.obs }}</p>
                <p class="text-xs text-slate-400 mt-0.5">
                  {{ fmtDateTime(ev.data_evento) }}<span v-if="ev.user_name"> · {{ ev.user_name }}</span>
                </p>
              </div>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';

defineOptions({ layout: AuthenticatedLayout });

defineProps({
  kpis: {
    type: Object,
    required: true,
    default: () => ({
      cronogramas_ativos: 0,
      cronogramas_encerrados: 0,
      cronogramas_rascunhos: 0,
      m3_entregues_mes: 0,
      prestadores_ativos: 0,
      viagens_pendentes_validar: 0,
    }),
  },
  eventosRecentes:   { type: Array, default: () => [] },
  cronogramasAtivos: { type: Array, default: () => [] },
});

function badgeTipo(tipo) {
  if (!tipo) return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
  if (tipo.includes('ativad') || tipo.includes('aprov')) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
  if (tipo.includes('encerrad') || tipo.includes('reject') || tipo.includes('reprov') || tipo.includes('rejeit')) return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300';
  if (tipo.includes('criad') || tipo.includes('registrad')) return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
  if (tipo.includes('prorrog')) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
  return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
}

function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('pt-BR');
}

function fmtDateTime(d) {
  if (!d) return '';
  return new Date(d).toLocaleString('pt-BR');
}
</script>
