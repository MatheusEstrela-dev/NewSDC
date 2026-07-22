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
      <StatCard
        title="Cronogramas ativos"
        :value="kpis.cronogramas_ativos"
        :icon="CheckCircleIcon"
        variant="success"
        clickable
        @click="router.visit(route('tdap.cronogramas.index'))"
      />
      <StatCard
        title="Encerrados"
        :value="kpis.cronogramas_encerrados"
        :icon="CheckIcon"
        variant="info"
      />
      <StatCard
        title="Rascunhos"
        :value="kpis.cronogramas_rascunhos"
        :icon="DocumentTextIcon"
        variant="warning"
      />
      <StatCard
        title="m³ entregues (mês)"
        :value="Number(kpis.m3_entregues_mes || 0).toLocaleString('pt-BR', { maximumFractionDigits: 0 })"
        :icon="TruckIcon"
        variant="info"
        :format-number="false"
      />
      <StatCard
        title="Prestadores ativos"
        :value="kpis.prestadores_ativos"
        :icon="BuildingIcon"
        variant="success"
      />
      <StatCard
        title="Viagens p/ validar"
        :value="kpis.viagens_pendentes_validar"
        :icon="ClockIcon"
        variant="warning"
        :clickable="kpis.viagens_pendentes_validar > 0"
        @click="router.visit(route('tdap.viagens.pendentes'))"
      />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <!-- Cronogramas ativos -->
      <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 border-t-4 border-t-emerald-400 dark:border-t-emerald-500/50 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 ring-1 ring-emerald-300 dark:ring-emerald-500/25">
              <TruckIcon class="w-4 h-4" />
            </span>
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Cronogramas ativos</h3>
          </div>
          <Link :href="route('tdap.cronogramas.index')">
            <Button variant="success" size="sm">Ver todos</Button>
          </Link>
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
      <div class="bg-white dark:bg-slate-900/40 rounded-xl border border-slate-200 dark:border-slate-700/40 border-t-4 border-t-cyan-400 dark:border-t-cyan-500/50 shadow-sm hover:shadow-md transition-shadow duration-300 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700/40 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="p-1.5 rounded-lg bg-cyan-100 dark:bg-cyan-500/15 text-cyan-700 dark:text-cyan-300 ring-1 ring-cyan-300 dark:ring-cyan-500/25">
              <ClockIcon class="w-4 h-4" />
            </span>
            <h3 class="text-sm font-semibold text-slate-500 uppercase tracking-wide">Eventos recentes</h3>
          </div>
          <Link :href="route('tdap.historicos.index')">
            <Button variant="info" size="sm">Auditoria completa</Button>
          </Link>
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
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TdapPageHeader from '@/Components/Organisms/Tdap/Header/TdapPageHeader.vue';
import StatCard from '@/Components/Molecules/Statistics/StatCard.vue';
import Button from '@/Components/Atoms/Button/Button.vue';
import { moduleIcon } from '@/Support/moduleIcons';
import TruckIcon from '@/Components/Icons/TruckIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import CheckIcon from '@/Components/Icons/CheckIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import BuildingIcon from '@/Components/Icons/BuildingIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';

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
