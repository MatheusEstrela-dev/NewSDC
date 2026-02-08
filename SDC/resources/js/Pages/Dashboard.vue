<template>
  <AuthenticatedLayout>
    <Head title="Dashboard" />

    <div class="dashboard-container">
      <!-- Header Padronizado -->
      <PageHeader
        title="Painel Gerencial"
        :description="`Exercício ${currentYear} - Visão consolidada dos processos de transferência e apoio aos municípios mineiros.`"
        :icon="HomeIcon"
        variant="gradient"
      />

      <!-- Grid de Métricas com Trends -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div v-for="metric in metrics" :key="metric.title" :class="metricCardClasses(metric.variant)">
          <div class="flex items-start justify-between gap-2 sm:gap-4">
            <div class="min-w-0 flex-1">
              <p class="text-xs sm:text-sm font-medium text-slate-400 dark:text-slate-400 mb-0.5 sm:mb-1 leading-tight">
                {{ metric.title }}
              </p>
              <p class="text-xl sm:text-2xl md:text-3xl font-bold text-slate-100 dark:text-slate-100 mb-0">
                {{ metric.value.toLocaleString('pt-BR') }}
              </p>
              <div class="flex items-center gap-2 mt-1.5">
                <span :class="trendClasses(metric.trend)">
                  <svg v-if="metric.trend > 0" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                  </svg>
                  <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                  {{ Math.abs(metric.trend) }}%
                </span>
                <span class="text-[11px] text-slate-500 hidden sm:inline">{{ metric.subtitle }}</span>
              </div>
            </div>
            <div :class="metricIconClasses(metric.variant)">
              <component :is="metric.icon" class="w-4 h-4 sm:w-5 sm:h-5 md:w-6 md:h-6" />
            </div>
          </div>
        </div>
      </div>

      <!-- Gráficos Principais: Barras + Donut -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico de Barras - Atendimentos por Mês -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Atendimentos por Mês</h3>
              <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Últimos 6 meses - todos os módulos</p>
            </div>
            <div class="flex gap-1 bg-slate-100 dark:bg-slate-700/50 rounded-lg p-0.5">
              <button
                v-for="period in ['6M', '12M']"
                :key="period"
                :class="[
                  'px-2.5 py-1 text-xs font-medium rounded-md transition-all',
                  barPeriod === period
                    ? 'bg-white dark:bg-slate-600 text-slate-900 dark:text-white shadow-sm'
                    : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300'
                ]"
                @click="barPeriod = period"
              >
                {{ period }}
              </button>
            </div>
          </div>
          <div class="p-5">
            <div class="flex items-end justify-between gap-2 sm:gap-3 h-48">
              <div
                v-for="(bar, i) in activeBarData"
                :key="i"
                class="flex-1 flex flex-col items-center gap-2 group"
              >
                <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity">
                  {{ bar.value }}
                </span>
                <div class="w-full relative rounded-t-md overflow-hidden" :style="{ height: barHeight(bar.value) + '%' }">
                  <div
                    class="absolute inset-0 rounded-t-md transition-all duration-500 group-hover:opacity-90"
                    :class="i === activeBarData.length - 1 ? 'bg-gradient-to-t from-cyan-600 to-cyan-400' : 'bg-gradient-to-t from-cyan-700/60 to-cyan-500/60 dark:from-cyan-600/40 dark:to-cyan-400/40'"
                  ></div>
                </div>
                <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">{{ bar.label }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Gráfico Donut - Distribuição por Módulo -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Distribuição por Módulo</h3>
            <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Proporção de registros por área</p>
          </div>
          <div class="p-5 flex flex-col sm:flex-row items-center gap-6">
            <!-- Donut -->
            <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex-shrink-0">
              <div class="donut-chart w-full h-full rounded-full" :style="donutStyle"></div>
              <div class="absolute inset-0 flex items-center justify-center">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-white dark:bg-slate-800/80 flex flex-col items-center justify-center shadow-inner">
                  <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100">{{ totalRegistros.toLocaleString('pt-BR') }}</span>
                  <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total</span>
                </div>
              </div>
            </div>
            <!-- Legenda -->
            <div class="flex-1 space-y-3 w-full">
              <div
                v-for="mod in moduleDistribution"
                :key="mod.name"
                class="flex items-center justify-between gap-3 group"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: mod.color }"></span>
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300 truncate">{{ mod.name }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ mod.value }}</span>
                  <span class="text-xs text-slate-500 dark:text-slate-400 w-10 text-right">{{ mod.percent }}%</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Sparklines de Módulos + PMDA + Timeline -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Sparklines - Visão por Módulo -->
        <div class="space-y-4">
          <div
            v-for="mod in moduleSparklines"
            :key="mod.name"
            class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 p-4 hover:shadow-xl transition-all cursor-pointer group"
          >
            <div class="flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <div :class="sparklineIconClasses(mod.variant)">
                  <component :is="mod.icon" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-slate-900 dark:text-slate-200 truncate">{{ mod.name }}</p>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ mod.value }}</span>
                    <span :class="trendClasses(mod.trend)" class="!text-[10px]">
                      <svg v-if="mod.trend > 0" class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                      </svg>
                      <svg v-else class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                      </svg>
                      {{ Math.abs(mod.trend) }}%
                    </span>
                  </div>
                </div>
              </div>
              <!-- Mini Sparkline SVG -->
              <svg class="w-20 h-8 flex-shrink-0" viewBox="0 0 80 32" fill="none">
                <polyline
                  :points="sparklinePoints(mod.data)"
                  fill="none"
                  :stroke="sparklineColor(mod.variant)"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <polyline
                  :points="sparklineAreaPoints(mod.data)"
                  :fill="sparklineAreaFill(mod.variant)"
                  stroke="none"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- PMDA em Análise -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-4 sm:px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">PMDA em Análise</h3>
              <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Aguardando intervenção técnica</p>
            </div>
            <span class="text-xs font-bold px-2 py-1 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400">
              {{ pmdaEmAnalise.length }} processos
            </span>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-slate-700/50">
            <div
              v-for="item in pmdaEmAnalise"
              :key="item.id"
              class="px-4 sm:px-5 py-3 hover:bg-slate-50 dark:hover:bg-slate-700/20 transition-colors"
            >
              <div class="flex items-center justify-between gap-3 mb-1">
                <span class="font-bold text-sm text-slate-900 dark:text-slate-200">{{ item.protocolo }}</span>
                <span :class="statusBadgeClasses(item.statusType)">
                  {{ item.status }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-xs text-slate-600 dark:text-slate-400">{{ item.municipio }}</span>
                <span class="text-[11px] text-slate-500 dark:text-slate-500">{{ item.data }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Timeline Últimas Movimentações -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Últimas Movimentações</h3>
            <div class="flex items-center gap-1.5">
              <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
              <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400">Tempo real</span>
            </div>
          </div>
          <div class="p-5">
            <div class="space-y-0">
              <div
                v-for="(h, index) in historico"
                :key="h.id"
                class="flex gap-3 pb-4 last:pb-0 relative"
              >
                <!-- Linha vertical -->
                <div class="flex flex-col items-center">
                  <div :class="['w-3 h-3 rounded-full flex-shrink-0 ring-4 ring-white dark:ring-slate-800/80', timelineDotColor(h.type)]"></div>
                  <div v-if="index < historico.length - 1" class="w-px flex-1 bg-slate-200 dark:bg-slate-700 mt-1"></div>
                </div>
                <div class="flex-1 -mt-0.5 pb-2">
                  <p class="font-semibold text-sm text-slate-900 dark:text-slate-200">{{ h.municipio }}</p>
                  <p class="text-xs mt-0.5 text-slate-600 dark:text-slate-400">{{ h.acao }}</p>
                  <div class="flex items-center gap-2 mt-1">
                    <span class="text-[11px] font-mono text-slate-500 dark:text-slate-500">{{ h.protocolo }}</span>
                    <span class="text-[11px] text-slate-400 dark:text-slate-500">{{ h.data }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Gráfico de Linhas + Quick Stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Gráfico de Linhas SVG - Tendência Mensal -->
        <div class="lg:col-span-2 rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Tendência Mensal</h3>
              <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Processos abertos vs concluídos</p>
            </div>
            <div class="flex items-center gap-4">
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-0.5 bg-cyan-500 rounded-full"></span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Abertos</span>
              </div>
              <div class="flex items-center gap-1.5">
                <span class="w-3 h-0.5 bg-emerald-500 rounded-full"></span>
                <span class="text-[11px] text-slate-500 dark:text-slate-400">Concluídos</span>
              </div>
            </div>
          </div>
          <div class="p-5">
            <svg class="w-full h-52" :viewBox="`0 0 ${lineChartWidth} 200`" preserveAspectRatio="none">
              <defs>
                <linearGradient id="areaGradientOpen" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#06b6d4" stop-opacity="0.3" />
                  <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.02" />
                </linearGradient>
                <linearGradient id="areaGradientClosed" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#10b981" stop-opacity="0.2" />
                  <stop offset="100%" stop-color="#10b981" stop-opacity="0.02" />
                </linearGradient>
              </defs>
              <!-- Grid lines -->
              <line v-for="i in 4" :key="'grid-'+i" x1="0" :y1="i * 40" :x2="lineChartWidth" :y2="i * 40" stroke="currentColor" class="text-slate-100 dark:text-slate-700/50" stroke-width="1" stroke-dasharray="4 4" />
              <!-- Y axis labels -->
              <text v-for="(label, i) in yAxisLabels" :key="'y-'+i" x="0" :y="i * 40 + 4" fill="currentColor" class="text-slate-400 dark:text-slate-500" font-size="10">{{ label }}</text>
              <!-- Area fills -->
              <path :d="lineAreaPath(trendAbertos)" fill="url(#areaGradientOpen)" />
              <path :d="lineAreaPath(trendConcluidos)" fill="url(#areaGradientClosed)" />
              <!-- Lines -->
              <path :d="linePath(trendAbertos)" fill="none" stroke="#06b6d4" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <path :d="linePath(trendConcluidos)" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
              <!-- Points Abertos -->
              <circle
                v-for="(pt, i) in linePoints(trendAbertos)"
                :key="'pt-open-'+i"
                :cx="pt.x"
                :cy="pt.y"
                r="4"
                fill="#06b6d4"
                stroke="white"
                stroke-width="2"
                class="opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
              />
              <!-- Points Concluídos -->
              <circle
                v-for="(pt, i) in linePoints(trendConcluidos)"
                :key="'pt-closed-'+i"
                :cx="pt.x"
                :cy="pt.y"
                r="4"
                fill="#10b981"
                stroke="white"
                stroke-width="2"
                class="opacity-0 hover:opacity-100 transition-opacity cursor-pointer"
              />
              <!-- X axis labels -->
              <text
                v-for="(label, i) in trendMonths"
                :key="'x-'+i"
                :x="30 + i * ((lineChartWidth - 40) / (trendMonths.length - 1))"
                y="198"
                fill="currentColor"
                class="text-slate-400 dark:text-slate-500"
                font-size="10"
                text-anchor="middle"
              >{{ label }}</text>
            </svg>
          </div>
        </div>

        <!-- Quick Stats - Indicadores Rápidos -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Indicadores Rápidos</h3>
            <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Resumo consolidado</p>
          </div>
          <div class="p-5 space-y-5">
            <!-- Municípios Atendidos -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <BuildingOfficeIcon class="w-4 h-4 text-cyan-500" />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Municípios Atendidos</span>
                </div>
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">142 <span class="text-xs font-normal text-slate-500">/ 853</span></span>
              </div>
              <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-cyan-400 transition-all duration-1000" style="width: 16.6%"></div>
              </div>
            </div>

            <!-- Taxa de Resolução -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <CheckCircleIcon class="w-4 h-4 text-emerald-500" />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Taxa de Resolução</span>
                </div>
                <span class="text-sm font-bold text-emerald-600 dark:text-emerald-400">94%</span>
              </div>
              <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-1000" style="width: 94%"></div>
              </div>
            </div>

            <!-- Tempo Médio de Resposta -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <ClockIcon class="w-4 h-4 text-amber-500" />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Tempo Médio Resposta</span>
                </div>
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">3.2 <span class="text-xs font-normal text-slate-500">dias</span></span>
              </div>
              <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-1000" style="width: 36%"></div>
              </div>
            </div>

            <!-- Treinamentos Realizados -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <BookOpenIcon class="w-4 h-4 text-violet-500" />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Treinamentos Realizados</span>
                </div>
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">28 <span class="text-xs font-normal text-slate-500">/ 45</span></span>
              </div>
              <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-violet-400 transition-all duration-1000" style="width: 62%"></div>
              </div>
            </div>

            <!-- Beneficiários Atendidos -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                  <HeartIcon class="w-4 h-4 text-red-500" />
                  <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Beneficiários (Ajuda Hum.)</span>
                </div>
                <span class="text-sm font-bold text-slate-900 dark:text-slate-100">1.247</span>
              </div>
              <div class="w-full h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                <div class="h-full rounded-full bg-gradient-to-r from-red-500 to-red-400 transition-all duration-1000" style="width: 78%"></div>
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
import { ref, computed } from 'vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';

// ─── Dados ────────────────────────────────────────
const currentYear = ref(new Date().getFullYear());
const barPeriod = ref('6M');

// Métricas com trends
const metrics = ref([
  { title: 'Em Edição', value: 24, trend: 12, subtitle: '3 novos hoje', variant: 'info', icon: PencilSquareIcon },
  { title: 'Em Análise', value: 5, trend: -8, subtitle: 'Tempo médio: 4 dias', variant: 'warning', icon: ClockIcon },
  { title: 'Aprovados', value: 77, trend: 15, subtitle: '12 esta semana', variant: 'success', icon: CheckCircleIcon },
  { title: 'Atendidos', value: 12, trend: 5, subtitle: '98% resolução', variant: 'danger', icon: CheckCircleIcon },
]);

// ─── Gráfico de Barras ────────────────────────────
const barData6M = ref([
  { label: 'Set', value: 45 },
  { label: 'Out', value: 62 },
  { label: 'Nov', value: 58 },
  { label: 'Dez', value: 71 },
  { label: 'Jan', value: 83 },
  { label: 'Fev', value: 47 },
]);

const barData12M = ref([
  { label: 'Mar', value: 32 },
  { label: 'Abr', value: 38 },
  { label: 'Mai', value: 41 },
  { label: 'Jun', value: 55 },
  { label: 'Jul', value: 48 },
  { label: 'Ago', value: 39 },
  { label: 'Set', value: 45 },
  { label: 'Out', value: 62 },
  { label: 'Nov', value: 58 },
  { label: 'Dez', value: 71 },
  { label: 'Jan', value: 83 },
  { label: 'Fev', value: 47 },
]);

const activeBarData = computed(() => barPeriod.value === '6M' ? barData6M.value : barData12M.value);

const maxBarValue = computed(() => Math.max(...activeBarData.value.map(b => b.value)));

function barHeight(value) {
  return Math.max(8, (value / maxBarValue.value) * 100);
}

// ─── Gráfico Donut ────────────────────────────────
const moduleDistribution = ref([
  { name: 'RAT', value: 892, percent: 35, color: '#06b6d4' },
  { name: 'Demandas', value: 637, percent: 25, color: '#f59e0b' },
  { name: 'Decretações', value: 510, percent: 20, color: '#ef4444' },
  { name: 'PAE', value: 306, percent: 12, color: '#10b981' },
  { name: 'Ajuda Humanitária', value: 204, percent: 8, color: '#8b5cf6' },
]);

const totalRegistros = computed(() => moduleDistribution.value.reduce((sum, m) => sum + m.value, 0));

const donutStyle = computed(() => {
  let cumulative = 0;
  const segments = moduleDistribution.value.map(m => {
    const start = cumulative;
    cumulative += m.percent;
    return `${m.color} ${start}% ${cumulative}%`;
  });
  return {
    background: `conic-gradient(${segments.join(', ')})`,
  };
});

// ─── Sparklines ───────────────────────────────────
const moduleSparklines = ref([
  { name: 'RAT', value: 156, trend: 18, variant: 'info', icon: DocumentTextIcon, data: [12, 19, 15, 22, 18, 25, 20] },
  { name: 'Demandas', value: 43, trend: -5, variant: 'warning', icon: ClipboardDocumentListIcon, data: [8, 12, 10, 7, 9, 6, 8] },
  { name: 'Decretações', value: 28, trend: 32, variant: 'danger', icon: ExclamationTriangleIcon, data: [3, 5, 4, 8, 6, 10, 9] },
]);

function sparklinePoints(data) {
  const max = Math.max(...data);
  const min = Math.min(...data);
  const range = max - min || 1;
  return data.map((v, i) => {
    const x = (i / (data.length - 1)) * 76 + 2;
    const y = 30 - ((v - min) / range) * 26 + 2;
    return `${x},${y}`;
  }).join(' ');
}

function sparklineAreaPoints(data) {
  const line = sparklinePoints(data);
  const lastX = (data.length - 1) / (data.length - 1) * 76 + 2;
  return `2,32 ${line} ${lastX},32`;
}

function sparklineColor(variant) {
  const colors = { info: '#06b6d4', warning: '#f59e0b', danger: '#ef4444', success: '#10b981' };
  return colors[variant] || '#06b6d4';
}

function sparklineAreaFill(variant) {
  const colors = { info: 'rgba(6,182,212,0.15)', warning: 'rgba(245,158,11,0.15)', danger: 'rgba(239,68,68,0.15)', success: 'rgba(16,185,129,0.15)' };
  return colors[variant] || 'rgba(6,182,212,0.15)';
}

function sparklineIconClasses(variant) {
  const map = {
    info: 'p-2 rounded-lg bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/25',
    warning: 'p-2 rounded-lg bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/25',
    danger: 'p-2 rounded-lg bg-red-500/15 text-red-400 ring-1 ring-red-500/25',
    success: 'p-2 rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/25',
  };
  return map[variant] || map.info;
}

// ─── PMDA ─────────────────────────────────────────
const pmdaEmAnalise = ref([
  { id: 1, protocolo: '2025/001', status: 'Análise Técnica', statusType: 'info', data: '20/01/2025', municipio: 'Belo Horizonte' },
  { id: 2, protocolo: '2025/002', status: 'Parecer', statusType: 'warning', data: '12/02/2025', municipio: 'Contagem' },
  { id: 3, protocolo: '2025/005', status: 'Aguard. Doc.', statusType: 'danger', data: '15/02/2025', municipio: 'Betim' },
  { id: 4, protocolo: '2025/008', status: 'Análise Técnica', statusType: 'info', data: '18/02/2025', municipio: 'Nova Lima' },
  { id: 5, protocolo: '2025/012', status: 'Triagem', statusType: 'success', data: '20/02/2025', municipio: 'Sabará' },
]);

function statusBadgeClasses(type) {
  const map = {
    info: 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-cyan-100 dark:bg-cyan-500/20 text-cyan-700 dark:text-cyan-400',
    warning: 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400',
    danger: 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
    success: 'px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400',
  };
  return map[type] || map.info;
}

// ─── Timeline ─────────────────────────────────────
const historico = ref([
  { id: 101, protocolo: '2025/001', municipio: 'Belo Horizonte', data: 'Há 2 horas', acao: 'Envio para análise', type: 'info' },
  { id: 102, protocolo: '2025/002', municipio: 'Contagem', data: 'Ontem', acao: 'Correção de documentos', type: 'warning' },
  { id: 103, protocolo: '2025/005', municipio: 'Betim', data: '15/02/2025', acao: 'Solicitação de vistoria', type: 'success' },
  { id: 104, protocolo: 'RAT-992', municipio: 'Ouro Preto', data: '10/02/2025', acao: 'Relatório finalizado', type: 'success' },
  { id: 105, protocolo: 'DEM-045', municipio: 'Uberlândia', data: '08/02/2025', acao: 'Nova demanda criada', type: 'info' },
]);

function timelineDotColor(type) {
  const map = {
    info: 'bg-cyan-500',
    warning: 'bg-amber-500',
    success: 'bg-emerald-500',
    danger: 'bg-red-500',
  };
  return map[type] || 'bg-cyan-500';
}

// ─── Gráfico de Linhas ───────────────────────────
const trendMonths = ref(['Set', 'Out', 'Nov', 'Dez', 'Jan', 'Fev']);
const trendAbertos = ref([42, 58, 52, 68, 75, 61]);
const trendConcluidos = ref([35, 48, 55, 60, 70, 58]);
const lineChartWidth = 600;

const maxTrendValue = computed(() => {
  const allValues = [...trendAbertos.value, ...trendConcluidos.value];
  return Math.max(...allValues);
});

const yAxisLabels = computed(() => {
  const max = maxTrendValue.value;
  return [max, Math.round(max * 0.75), Math.round(max * 0.5), Math.round(max * 0.25), 0].map(v => v.toString());
});

function linePoints(data) {
  const maxVal = maxTrendValue.value;
  const padding = 30;
  const chartW = lineChartWidth - padding - 10;
  return data.map((v, i) => ({
    x: padding + (i / (data.length - 1)) * chartW,
    y: 10 + (1 - v / maxVal) * 170,
  }));
}

function linePath(data) {
  const pts = linePoints(data);
  return pts.map((pt, i) => `${i === 0 ? 'M' : 'L'} ${pt.x} ${pt.y}`).join(' ');
}

function lineAreaPath(data) {
  const pts = linePoints(data);
  const padding = 30;
  const chartW = lineChartWidth - padding - 10;
  const basePath = pts.map((pt, i) => `${i === 0 ? 'M' : 'L'} ${pt.x} ${pt.y}`).join(' ');
  return `${basePath} L ${padding + chartW} 180 L ${padding} 180 Z`;
}

// ─── Helpers de estilo ────────────────────────────
const variantBorderMap = {
  info: 'border-cyan-500/25 dark:border-cyan-500/25 border-cyan-200',
  success: 'border-emerald-500/25 dark:border-emerald-500/25 border-emerald-200',
  warning: 'border-amber-500/25 dark:border-amber-500/25 border-amber-200',
  danger: 'border-red-500/25 dark:border-red-500/25 border-red-200',
};

const variantIconMap = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-1 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-1 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-1 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300',
  danger: 'bg-red-500/15 dark:bg-red-500/15 bg-red-100 text-red-300 dark:text-red-300 text-red-700 ring-1 ring-red-500/25 dark:ring-red-500/25 ring-red-300',
};

function metricCardClasses(variant) {
  return `rounded-lg sm:rounded-xl border backdrop-blur-sm px-3 py-3 sm:px-4 sm:py-4 md:px-5 md:py-4 transition-all duration-300 hover:shadow-lg hover:scale-[1.02] active:scale-[0.98] touch-manipulation bg-slate-900/60 dark:bg-slate-900/60 bg-white hover:bg-slate-900/80 dark:hover:bg-slate-900/80 hover:bg-slate-50 ${variantBorderMap[variant]}`;
}

function metricIconClasses(variant) {
  return `p-1.5 sm:p-2 md:p-3 rounded-md sm:rounded-lg ${variantIconMap[variant]}`;
}

function trendClasses(trend) {
  return [
    'inline-flex items-center gap-0.5 text-xs font-bold px-1.5 py-0.5 rounded-full',
    trend > 0
      ? 'bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400'
      : 'bg-red-100 dark:bg-red-500/20 text-red-700 dark:text-red-400',
  ];
}
</script>

<style scoped>
.dashboard-container {
  @apply w-full min-h-screen bg-slate-50 dark:bg-slate-950;
}

.donut-chart {
  transition: transform 0.3s ease;
}

.donut-chart:hover {
  transform: scale(1.03);
}
</style>
