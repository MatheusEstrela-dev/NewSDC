<template>
    <div>
        <Head title="Dashboard" />

        <div class="dashboard-container">
          <!-- Header Padronizado -->
          <div data-tour="painel-header">
            <PageHeader
              title="Painel Gerencial"
              :description="`Exercício ${currentYear} - Visão consolidada dos processos.`"
              :icon="HomeIcon"
              variant="gradient"
            />
          </div>

          <!-- Área de Drop Principal -->
          <draggable
            v-model="dashboardItems"
            item-key="id"
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4 sm:gap-6 mt-6"
            handle=".drag-handle"
            ghost-class="ghost-card"
            :animation="200"
          >
            <template #item="{ element }">
              <div
                :class="element.colSpan"
                class="min-h-[100px]"
                :data-tour="element.id.startsWith('metric-') ? 'kpi-card' : (element.id === 'chart-bar' || element.id === 'chart-donut' ? 'charts-card' : undefined)"
              >
                <!-- Wrapper com Handle de Arraste -->
                <div class="h-full relative group/item">
                  <!-- Botão de Arraste -->
                  <div class="drag-handle absolute top-2 right-2 z-30 p-1.5 rounded-md bg-white/80 dark:bg-slate-800/80 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 shadow-sm opacity-0 group-hover/item:opacity-100 transition-opacity cursor-grab active:cursor-grabbing hover:bg-slate-100 dark:hover:bg-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                  </div>

                  <!-- Renderização Dinâmica do Widget -->
                  <component 
                    :is="element.component" 
                    v-bind="element.props"
                    class="h-full"
                    @select-module="openModuleModal"
                  />
                </div>
              </div>
            </template>
          </draggable>

          <!-- Modal de Detalhes do Módulo -->
          <Modal :show="isModalOpen" @close="closeModal" maxWidth="lg">
            <div v-if="selectedModule" class="bg-white dark:bg-slate-800 overflow-hidden relative">
              <!-- Decorative Header Background -->
              <div class="absolute top-0 left-0 right-0 h-24 bg-gradient-to-br opacity-20" :class="variantGradientClass(selectedModule.variant)"></div>
              
              <div class="relative px-6 py-6">
                <!-- Header -->
                <div class="flex items-start justify-between mb-6">
                  <div class="flex items-center gap-4">
                    <div class="p-3 rounded-xl shadow-lg ring-1 ring-black/5 dark:ring-white/10" :class="metricIconClasses(selectedModule.variant)">
                      <component :is="selectedModule.icon" class="w-8 h-8" />
                    </div>
                    <div>
                      <h2 class="text-xl font-bold text-slate-900 dark:text-white">{{ selectedModule.name }}</h2>
                      <p class="text-sm text-slate-500 dark:text-slate-400 text-transparent bg-clip-text bg-gradient-to-r from-slate-500 to-slate-400 font-medium">
                        Visão detalhada
                      </p>
                    </div>
                  </div>
                  <button @click="closeModal" class="text-slate-400 hover:text-slate-500 transition-colors p-1 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-full">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                  <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-100 dark:border-slate-700">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Total Registros</p>
                    <div class="flex items-end gap-2">
                      <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ selectedModule.value }}</span>
                      <span :class="trendClasses(selectedModule.trend)" class="mb-1">
                        {{ Math.abs(selectedModule.trend) }}%
                      </span>
                    </div>
                  </div>
                  <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-700/50 border border-slate-100 dark:border-slate-700">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Status Ativo</p>
                    <div class="flex items-center gap-2 mt-1">
                      <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75" :class="selectedModule.trend > 0 ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3" :class="selectedModule.trend > 0 ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                      </span>
                      <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                        {{ selectedModule.trend > 0 ? 'Normal' : 'Atenção' }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Content Placeholder -->
                <div class="space-y-3 mb-6">
                  <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <ClockIcon class="w-4 h-4 text-slate-400" />
                    Últimas Atualizações
                  </h4>
                  <div class="space-y-2">
                    <div v-for="i in 3" :key="i" class="flex items-center justify-between p-2.5 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors border border-transparent hover:border-slate-100 dark:hover:border-slate-700">
                      <div class="flex items-center gap-3">
                        <div class="w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                        <span class="text-sm text-slate-600 dark:text-slate-300">Atualização de registro #{{ 1000 + i }}</span>
                      </div>
                      <span class="text-xs text-slate-400">{{ i * 15 }} min atrás</span>
                    </div>
                  </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                  <button @click="closeModal" class="flex-1 px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors">
                    Cancelar
                  </button>
                  <button class="flex-1 px-4 py-2 text-sm font-medium text-white rounded-lg hover:opacity-90 focus:ring-2 focus:ring-offset-2 transition-colors shadow-lg shadow-blue-500/20" :class="variantButtonClass(selectedModule.variant)">
                    Acessar Módulo
                  </button>
                </div>
              </div>
            </div>
          </Modal>

        </div>
    </div>
</template>

<script setup>
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import Modal from '@/Components/Modal.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import draggable from '@/lib/vuedraggable-src/vuedraggable.js';
import { Head, usePage } from '@inertiajs/vue3';
import { defineAsyncComponent, markRaw, nextTick, onMounted, ref } from 'vue';
import { useWelcomeTour } from '@/composables/auth/useWelcomeTour';

defineOptions({ layout: AuthenticatedLayout });

// Tour de onboarding: dispara apenas se o backend sinalizar
// auth.show_welcome_tour=true (vide HandleInertiaRequests::share).
const inertiaPage = usePage();
const { startWelcomeTour } = useWelcomeTour({
    userName: inertiaPage.props.auth?.user?.name ?? '',
});

onMounted(async () => {
    if (!inertiaPage.props.auth?.show_welcome_tour) return;

    // Garante que sidebar, topbar e widgets async ja estejam no DOM antes
    // do Shepherd resolver os seletores [data-tour="*"].
    await nextTick();
    requestAnimationFrame(() => {
        requestAnimationFrame(startWelcomeTour);
    });
});

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

const props = defineProps({
    ratAbertas:         { type: Number, default: 0 },
    paeEmAnalise:       { type: Number, default: 0 },
    decretosAprovados:  { type: Number, default: 0 },
    demandasConcluidas: { type: Number, default: 0 },
    ratTrend:           { type: Number, default: 0 },
    paeTrend:           { type: Number, default: 0 },
    decretoTrend:       { type: Number, default: 0 },
    demandaTrend:       { type: Number, default: 0 },
    moduleDistribution: { type: Array,  default: () => [] },
    barData6M:          { type: Array,  default: () => [] },
    barData12M:         { type: Array,  default: () => [] },
    sparklines:         { type: Array,  default: () => [] },
});

const currentYear = ref(new Date().getFullYear());

// Estado do Modal
const isModalOpen = ref(false);
const selectedModule = ref(null);

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
        props: { title: 'RAT Abertas', value: props.ratAbertas, trend: props.ratTrend, subtitle: 'Total de ocorrências', variant: 'info', icon: markRaw(PencilSquareIcon) }
    },
    {
        id: 'metric-2',
        component: markRaw(DashboardMetricCard),
        colSpan: 'col-span-1 lg:col-span-3',
        props: { title: 'PAE Em Análise', value: props.paeEmAnalise, trend: props.paeTrend, subtitle: 'Aguardando análise', variant: 'warning', icon: markRaw(ClockIcon) }
    },
    {
        id: 'metric-3',
        component: markRaw(DashboardMetricCard),
        colSpan: 'col-span-1 lg:col-span-3',
        props: { title: 'Decretos Aprovados', value: props.decretosAprovados, trend: props.decretoTrend, subtitle: 'Reconhecidos', variant: 'success', icon: markRaw(CheckCircleIcon) }
    },
    {
        id: 'metric-4',
        component: markRaw(DashboardMetricCard),
        colSpan: 'col-span-1 lg:col-span-3',
        props: { title: 'Demandas Concluídas', value: props.demandasConcluidas, trend: props.demandaTrend, subtitle: 'Resolvidas e fechadas', variant: 'danger', icon: markRaw(CheckCircleIcon) }
    },

    // Gráficos Principais (Linha 2)
    {
        id: 'chart-bar',
        component: markRaw(BarChartWidget),
        colSpan: 'col-span-1 lg:col-span-6',
        props: { barData6M: props.barData6M, barData12M: props.barData12M }
    },
    {
        id: 'chart-donut',
        component: markRaw(DonutChartWidget),
        colSpan: 'col-span-1 lg:col-span-6',
        props: { moduleDistribution: props.moduleDistribution }
    },

    // Linha 3 (Sparklines, PMDA, Timeline)
    {
        id: 'sparklines',
        component: markRaw(SparklinesWidget),
        colSpan: 'col-span-1 lg:col-span-4',
        props: { sparklines: props.sparklines }
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

</script>

<style scoped>
.dashboard-container {
  @apply w-full pb-8 bg-slate-50 dark:bg-slate-950;
}

.ghost-card {
  opacity: 0.5;
  background: #f1f5f9; /* slate-100 */
  border: 2px dashed #cbd5e1; /* slate-300 */
}
.dark .ghost-card {
  background: #1e293b; /* slate-800 */
  border-color: #475569; /* slate-600 */
}
</style>
