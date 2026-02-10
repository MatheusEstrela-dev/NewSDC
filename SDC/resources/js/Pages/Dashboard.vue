<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="dashboard-container p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto space-y-8">
      <!-- Header Padronizado -->
      <PageHeader
        title="Painel Gerencial"
        :description="`Exercício ${currentYear} - Visão consolidada dos processos.`"
        :icon="HomeIcon"
        variant="gradient"
      />

      <!-- Área de Drop Principal -->
      <draggable
        v-model="dashboardItems"
        item-key="id"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-6"
        handle=".drag-handle"
        ghost-class="ghost-card"
        :animation="200"
      >
        <template #item="{ element }">
          <div :class="element.colSpan" class="min-h-[100px]">
            <!-- Wrapper com Handle de Arraste (opcional, pode ser o próprio card) -->
            <div class="h-full relative group/item">
              <!-- Botão de Arraste (Visível ao passar o mouse) -->
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
              />
            </div>
          </div>
        </template>
      </draggable>

    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import draggable from 'vuedraggable';
import { Head } from '@inertiajs/vue3';
import { ref, shallowRef } from 'vue';

// Importação dos Widgets
import DashboardMetricCard from '@/Components/Dashboard/Widgets/DashboardMetricCard.vue';
import BarChartWidget from '@/Components/Dashboard/Widgets/BarChartWidget.vue';
import DonutChartWidget from '@/Components/Dashboard/Widgets/DonutChartWidget.vue';
import PmdaListWidget from '@/Components/Dashboard/Widgets/PmdaListWidget.vue';
import RadarChartWidget from '@/Components/Dashboard/Widgets/RadarChartWidget.vue';
import SparklinesWidget from '@/Components/Dashboard/Widgets/SparklinesWidget.vue';
import TimelineWidget from '@/Components/Dashboard/Widgets/TimelineWidget.vue';
import TrendChartWidget from '@/Components/Dashboard/Widgets/TrendChartWidget.vue';

// Ícones para Métricas (passados via props)
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';

const currentYear = ref(new Date().getFullYear());

// Definição dos Itens do Dashboard
const dashboardItems = ref([
  // Métricas (Linha 1)
  { 
    id: 'metric-1', 
    component: shallowRef(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Em Edição', value: 24, trend: 12, subtitle: '3 novos hoje', variant: 'info', icon: PencilSquareIcon } 
  },
  { 
    id: 'metric-2', 
    component: shallowRef(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Em Análise', value: 5, trend: -8, subtitle: 'Tempo médio: 4 dias', variant: 'warning', icon: ClockIcon } 
  },
  { 
    id: 'metric-3', 
    component: shallowRef(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Aprovados', value: 77, trend: 15, subtitle: '12 esta semana', variant: 'success', icon: CheckCircleIcon } 
  },
  { 
    id: 'metric-4', 
    component: shallowRef(DashboardMetricCard), 
    colSpan: 'col-span-1 lg:col-span-3', 
    props: { title: 'Atendidos', value: 12, trend: 5, subtitle: '98% resolução', variant: 'danger', icon: CheckCircleIcon } 
  },
  
  // Gráficos Principais (Linha 2)
  { 
    id: 'chart-bar', 
    component: shallowRef(BarChartWidget), 
    colSpan: 'col-span-1 lg:col-span-6' 
  },
  { 
    id: 'chart-donut', 
    component: shallowRef(DonutChartWidget), 
    colSpan: 'col-span-1 lg:col-span-6' 
  },

  // Linha 3 (Sparklines, PMDA, Timeline)
  { 
    id: 'sparklines', 
    component: shallowRef(SparklinesWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },
  { 
    id: 'pmda-list', 
    component: shallowRef(PmdaListWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },
  { 
    id: 'timeline', 
    component: shallowRef(TimelineWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },

  // Linha 4 (Tendência + Radar)
  { 
    id: 'chart-trend', 
    component: shallowRef(TrendChartWidget), 
    colSpan: 'col-span-1 lg:col-span-8' 
  },
  { 
    id: 'chart-radar', 
    component: shallowRef(RadarChartWidget), 
    colSpan: 'col-span-1 lg:col-span-4' 
  },
]);

</script>

<style scoped>
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
