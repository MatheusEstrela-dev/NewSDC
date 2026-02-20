<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 p-8">
      <!-- Banner Ano Fiscal -->
      <div class="relative px-6 py-5 rounded-2xl shadow-lg mb-8 overflow-hidden bg-gradient-to-r from-slate-800 to-slate-900 dark:from-slate-800 dark:to-slate-900 from-blue-50 to-indigo-50 border border-blue-100 dark:border-transparent">
        <div class="relative z-10">
          <p class="text-xs uppercase font-bold tracking-widest mb-1 text-blue-200/80 dark:text-blue-200/80 text-blue-600">
            Painel Gerencial
          </p>
          <h2 class="text-3xl font-bold tracking-tight text-white dark:text-white text-slate-900">Exercício {{ currentYear }}</h2>
          <p class="text-sm mt-1 max-w-md text-slate-400 dark:text-slate-400 text-slate-600">
            Visão consolidada dos processos de transferência e apoio aos municípios mineiros.
          </p>
        </div>
      </div>

      <!-- Grid de Métricas -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <div
          v-for="(metric, key) in metrics"
          :key="key"
          class="rounded-xl p-5 shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50"
        >
          <div class="flex justify-between items-start mb-4">
            <div :class="[metric.color, 'w-10 h-10 rounded-lg flex items-center justify-center text-white']">
              {{ metric.icon }}
            </div>
            <span class="text-xs font-bold px-2 py-1 rounded-full text-slate-400 dark:text-slate-400 text-slate-600 bg-slate-50 dark:bg-slate-700/50 bg-slate-100">
              +2%
            </span>
          </div>
          <div>
            <p class="text-3xl font-bold mt-1 text-slate-800 dark:text-slate-200 text-slate-900">{{ metric.val }}</p>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400 text-slate-600">{{ metric.label }}</p>
          </div>
        </div>
      </div>

      <!-- Conteúdo Principal -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Tabela PMDA -->
        <div class="lg:col-span-2 rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 text-slate-900">PMDA em Análise</h3>
            <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400 text-slate-600">Processos aguardando intervenção técnica</p>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="text-xs uppercase font-bold border-b bg-slate-50 dark:bg-slate-900/50 bg-slate-100 text-slate-400 dark:text-slate-400 text-slate-600 border-slate-100 dark:border-slate-700">
                <tr>
                  <th class="px-6 py-4">Protocolo</th>
                  <th class="px-6 py-4">Município</th>
                  <th class="px-6 py-4">Status</th>
                  <th class="px-6 py-4">Data</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50 dark:divide-slate-700/50 divide-slate-100">
                <tr v-for="item in pmdaEmAnalise" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 hover:bg-slate-100">
                  <td class="px-6 py-4 font-medium text-slate-900 dark:text-slate-200 text-slate-900">{{ item.protocolo }}</td>
                  <td class="px-6 py-4 text-slate-700 dark:text-slate-300 text-slate-700">{{ item.municipio }}</td>
                  <td class="px-6 py-4">
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-500/20 bg-blue-100 text-blue-800 dark:text-blue-400 text-blue-800">
                      {{ item.status }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-xs text-slate-500 dark:text-slate-400 text-slate-500">{{ item.data }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Timeline -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-lg text-slate-800 dark:text-slate-200 text-slate-900">Últimas Movimentações</h3>
          </div>
          <div class="p-6">
            <div class="space-y-4">
              <div
                v-for="h in historico"
                :key="h.id"
                class="flex gap-4 pb-4 border-b last:border-0 border-slate-100 dark:border-slate-700"
              >
                <div class="w-4 h-4 rounded-full bg-blue-500 mt-1"></div>
                <div class="flex-1">
                  <p class="font-semibold text-sm text-slate-800 dark:text-slate-200 text-slate-900">{{ h.municipio }}</p>
                  <p class="text-sm mt-0.5 text-slate-600 dark:text-slate-400 text-slate-700">{{ h.acao }}</p>
                  <p class="text-xs mt-1 font-mono text-slate-400 dark:text-slate-500 text-slate-500">{{ h.protocolo }}</p>
                  <span class="text-xs text-slate-500 dark:text-slate-400 text-slate-500">{{ h.data }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthenticatedLayout });

// Widgets carregados sob demanda (lazy) para reduzir bundle inicial
const DashboardMetricCard = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/DashboardMetricCard.vue'));
const BarChartWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/BarChartWidget.vue'));
const DonutChartWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/DonutChartWidget.vue'));
const SparklinesWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/SparklinesWidget.vue'));
const PmdaListWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/PmdaListWidget.vue'));
const TimelineWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/TimelineWidget.vue'));
const TrendChartWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/TrendChartWidget.vue'));
const RadarChartWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/RadarChartWidget.vue'));
const PlanConMunicipiosWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/PlanConMunicipiosWidget.vue'));
const PlanConSituacaoWidget = defineAsyncComponent(() => import('@/Components/Dashboard/Widgets/PlanConSituacaoWidget.vue'));

// Ícones para Métricas (leves, podem ser eager)
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';

const currentYear = ref(new Date().getFullYear());

const metrics = ref({
  emEdicao: { val: 24, label: 'Em Edição', color: 'bg-blue-600', icon: '✏️' },
  emAnalise: { val: 5, label: 'Em Análise', color: 'bg-amber-500', icon: '⏰' },
  aprovados: { val: 77, label: 'Aprovados', color: 'bg-emerald-600', icon: '✓' },
  atendidos: { val: 12, label: 'Atendidos', color: 'bg-indigo-600', icon: '✓✓' },
});

function openModuleModal(moduleData) {
  selectedModule.value = moduleData;
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  setTimeout(() => {
    selectedModule.value = null;
  }, 300); // Limpa dados após animação de saída
}

// Helpers de Estilo para o Modal
const variantIconMap = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300',
  danger: 'bg-red-500/15 dark:bg-red-500/15 bg-red-100 text-red-300 dark:text-red-300 text-red-700 ring-red-500/25 dark:ring-red-500/25 ring-red-300',
  primary: 'bg-violet-500/15 dark:bg-violet-500/15 bg-violet-100 text-violet-300 dark:text-violet-300 text-violet-700 ring-violet-500/25 dark:ring-violet-500/25 ring-violet-300',
};

function metricIconClasses(variant) {
  return variantIconMap[variant] || variantIconMap.info;
}

function variantGradientClass(variant) {
  const map = {
    info: 'from-cyan-400 to-blue-500',
    success: 'from-emerald-400 to-green-500',
    warning: 'from-amber-400 to-orange-500',
    danger: 'from-rose-400 to-red-500',
    primary: 'from-violet-400 to-purple-500',
  };
  return map[variant] || 'from-slate-400 to-slate-500';
}

function variantButtonClass(variant) {
  const map = {
    info: 'bg-cyan-600 hover:bg-cyan-700 focus:ring-cyan-500',
    success: 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
    warning: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    danger: 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
    primary: 'bg-violet-600 hover:bg-violet-700 focus:ring-violet-500',
  };
  return map[variant] || 'bg-slate-600';
}

function trendClasses(trend) {
  if (trend > 0) return 'text-emerald-500 dark:text-emerald-400 text-sm font-bold bg-emerald-500/10 dark:bg-emerald-500/10 px-1.5 py-0.5 rounded-md';
  if (trend < 0) return 'text-rose-500 dark:text-rose-400 text-sm font-bold bg-rose-500/10 dark:bg-rose-500/10 px-1.5 py-0.5 rounded-md';
  return 'text-slate-400 dark:text-slate-500 text-sm font-bold bg-slate-500/10 px-1.5 py-0.5 rounded-md';
}

// Definição dos Itens do Dashboard
const dashboardItems = ref([
  // Métricas (Linha 1)
  { 
    id: 'metric-1', 
    component: markRaw(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Em Edição', value: 24, trend: 12, subtitle: '3 novos hoje', variant: 'info', icon: markRaw(PencilSquareIcon) } 
  },
  { 
    id: 'metric-2', 
    component: markRaw(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Em Análise', value: 5, trend: -8, subtitle: 'Tempo médio: 4 dias', variant: 'warning', icon: markRaw(ClockIcon) } 
  },
  { 
    id: 'metric-3', 
    component: markRaw(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Aprovados', value: 77, trend: 15, subtitle: '12 esta semana', variant: 'success', icon: markRaw(CheckCircleIcon) } 
  },
  { 
    id: 'metric-4', 
    component: markRaw(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Atendidos', value: 12, trend: 5, subtitle: '98% resolução', variant: 'danger', icon: markRaw(CheckCircleIcon) } 
  },
  
  // Gráficos Principais (Linha 2)
  { 
    id: 'chart-bar', 
    component: markRaw(BarChartWidget), 
    colSpan: 'col-span-1 lg:col-span-6' 
  },
  { 
    id: 'chart-donut', 
    component: markRaw(DonutChartWidget), 
    colSpan: 'col-span-1 lg:col-span-6' 
  },

  // Linha 3 (Sparklines, PMDA, Timeline)
  { 
    id: 'sparklines', 
    component: markRaw(SparklinesWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },
  { 
    id: 'pmda-list', 
    component: markRaw(PmdaListWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },
  { 
    id: 'timeline', 
    component: markRaw(TimelineWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },

  // Linha 4 (Tendência + Radar)
  {
    id: 'chart-trend',
    component: markRaw(TrendChartWidget),
    colSpan: 'col-span-1 lg:col-span-8'
  },
  {
    id: 'chart-radar',
    component: markRaw(RadarChartWidget),
    colSpan: 'col-span-1 lg:col-span-4'
  },

  // Linha 5 (Plano de Contingencia - 2 blocos)
  {
    id: 'plancon-municipios',
    component: markRaw(PlanConMunicipiosWidget),
    colSpan: 'col-span-1 lg:col-span-6'
  },
  {
    id: 'plancon-situacao',
    component: markRaw(PlanConSituacaoWidget),
    colSpan: 'col-span-1 lg:col-span-6'
  },
]);

const historico = ref([
  { id: 101, protocolo: '2025/001', municipio: 'Belo Horizonte', data: 'Há 2 horas', acao: 'Envio para análise' },
  { id: 102, protocolo: '2025/002', municipio: 'Contagem', data: 'Ontem', acao: 'Correção de documentos' },
  { id: 103, protocolo: '2025/005', municipio: 'Betim', data: '15/02/2025', acao: 'Solicitação de vistoria' },
  { id: 104, protocolo: 'RAT-992', municipio: 'Ouro Preto', data: '10/02/2025', acao: 'Relatório finalizado' },
]);
</script>
