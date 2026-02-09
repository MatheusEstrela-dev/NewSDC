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
        <div
          v-for="(metric, index) in metrics"
          :key="metric.title"
          :style="{ animationDelay: `${index * 100}ms` }"
          :class="['animate-fade-in-up tilt-card relative overflow-hidden group', metricCardClasses(metric.variant)]"
          @mousemove="handleTilt"
          @mouseleave="resetTilt"
        >
          <!-- Spotlight Effect Layer -->
          <div class="pointer-events-none absolute -inset-px opacity-0 group-hover:opacity-100 transition-opacity duration-300"
               style="background: radial-gradient(600px circle at var(--mouse-x) var(--mouse-y), rgba(255,255,255,0.06), transparent 40%); z-index: 1;">
          </div>

          <div class="flex items-start justify-between gap-2 sm:gap-4 relative z-10">
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
          <div class="p-5 relative">
            <!-- Custom Tooltip for Bars -->
            <div 
              v-if="hoveredBarIndex !== null"
              class="absolute z-50 pointer-events-none bg-slate-900/95 text-white px-3 py-1.5 rounded-lg shadow-xl border border-slate-700 text-xs transition-all duration-200 whitespace-nowrap"
              :style="{ 
                left: `${(30 + hoveredBarIndex * ((600 - 60) / (activeBarData.length - 1))) / 6}%`, 
                top: `${200 - (activeBarData[hoveredBarIndex].value / maxBarValue) * 160 - 40}px`,
                transform: 'translateX(-50%)'
              }"
            >
              <div class="font-bold text-center">{{ activeBarData[hoveredBarIndex].label }}</div>
              <div class="text-cyan-400 font-bold text-sm text-center">{{ activeBarData[hoveredBarIndex].value }} <span class="text-[10px] text-slate-400 font-normal">atendimentos</span></div>
            </div>

            <svg class="w-full h-52 overflow-visible" viewBox="0 0 600 200" preserveAspectRatio="none">
              <defs>
                <linearGradient id="barGradient" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="0%" stop-color="#06b6d4" stop-opacity="1" />
                  <stop offset="100%" stop-color="#06b6d4" stop-opacity="0.3" />
                </linearGradient>
                <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                  <feGaussianBlur stdDeviation="2" result="coloredBlur" />
                  <feMerge>
                    <feMergeNode in="coloredBlur" />
                    <feMergeNode in="SourceGraphic" />
                  </feMerge>
                </filter>
              </defs>

              <!-- Grid lines -->
              <line v-for="i in 4" :key="'grid-bar-'+i" x1="0" :y1="i * 40" :x2="600" :y2="i * 40" stroke="currentColor" class="text-slate-100 dark:text-slate-700/50" stroke-width="1" stroke-dasharray="4 4" />
              
              <!-- Bars -->
              <g v-for="(item, index) in activeBarData" :key="item.label + barPeriod">
                <!-- Bar Rect -->
                <rect
                  :x="30 + index * ((600 - 60) / (activeBarData.length - 1)) - (activeBarData.length > 6 ? 8 : 12)"
                  :y="200 - (item.value / maxBarValue) * 160"
                  :width="activeBarData.length > 6 ? 16 : 24"
                  :height="(item.value / maxBarValue) * 160"
                  rx="4"
                  fill="url(#barGradient)"
                  class="transition-all duration-500 cubic-bezier(0.4, 0, 0.2, 1) cursor-pointer"
                  :class="[
                    hoveredBarIndex === index ? 'opacity-100 filter url(#glow)' : 'opacity-80 hover:opacity-100'
                  ]"
                  :style="{ 
                    transformOrigin: 'bottom',
                    animation: `growUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards ${index * 50}ms`
                  }"
                  @mouseenter="hoveredBarIndex = index"
                  @mouseleave="hoveredBarIndex = null"
                />
                
                <!-- X Axis Label -->
                <text
                  :x="30 + index * ((600 - 60) / (activeBarData.length - 1))"
                  y="215"
                  text-anchor="middle"
                  class="text-[10px] font-medium transition-colors duration-200"
                  :class="hoveredBarIndex === index ? 'fill-cyan-500 font-bold' : 'fill-slate-400 dark:fill-slate-500'"
                >
                  {{ item.label }}
                </text>
              </g>
            </svg>
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
            <div class="relative w-44 h-44 sm:w-48 sm:h-48 flex-shrink-0 flex items-center justify-center group/donut">
              <svg viewBox="0 0 100 100" class="w-full h-full transform -rotate-90">
                <!-- Background Circle -->
                <circle cx="50" cy="50" r="40" stroke="currentColor" stroke-width="8" class="text-slate-100 dark:text-slate-800/50" fill="none" />
                
                <!-- Segments -->
                <circle
                  v-for="(segment, index) in donutSegments"
                  :key="segment.name"
                  cx="50"
                  cy="50"
                  r="40"
                  fill="none"
                  stroke-width="8"
                  stroke-linecap="round"
                  :stroke="segment.color"
                  :stroke-dasharray="segment.dashArray"
                  :stroke-dashoffset="segment.dashOffset"
                  class="transition-all duration-300 ease-out cursor-pointer origin-center hover:opacity-100"
                  :class="[
                    hoveredSegment === index ? 'scale-110 opacity-100 brightness-110 drop-shadow-[0_0_8px_rgba(0,0,0,0.5)]' : 'scale-100 opacity-90 hover:scale-105'
                  ]"
                  @mouseenter="hoveredSegment = index"
                  @mouseleave="hoveredSegment = null"
                />
              </svg>

              <!-- Center Info -->
              <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-24 h-24 sm:w-28 sm:h-28 rounded-full bg-white dark:bg-slate-900 shadow-[inset_0_2px_10px_rgba(0,0,0,0.1)] flex flex-col items-center justify-center z-10 transition-transform duration-300"
                     :class="{'scale-105': hoveredSegment !== null}">
                  <span class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-slate-100 transition-colors duration-300"
                        :style="{ color: hoveredSegment !== null ? moduleDistribution[hoveredSegment].color : '' }">
                    {{ hoveredSegment !== null ? moduleDistribution[hoveredSegment].value : totalRegistros.toLocaleString('pt-BR') }}
                  </span>
                  <span class="text-[10px] font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    {{ hoveredSegment !== null ? moduleDistribution[hoveredSegment].name : 'Total' }}
                  </span>
                </div>
              </div>
            </div>
            <!-- Legenda -->
            <div class="flex-1 space-y-3 w-full">
              <div
                v-for="(mod, index) in moduleDistribution"
                :key="mod.name"
                class="flex items-center justify-between gap-3 group cursor-pointer transition-all duration-300 hover:bg-slate-50 dark:hover:bg-slate-800/50 p-2 rounded-lg"
                :class="{'bg-slate-50 dark:bg-slate-800/50 scale-[1.02] shadow-sm ring-1 ring-slate-100 dark:ring-slate-700': hoveredSegment === index}"
                @mouseenter="hoveredSegment = index"
                @mouseleave="hoveredSegment = null"
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
            class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 p-4 transition-all duration-300 cursor-pointer group overflow-hidden relative tilt-card"
            @mousemove="handleTilt"
            @mouseleave="resetTilt"
            @click="openModuleDetails(mod)"
          >
            <!-- Spotlight Effect -->
            <div class="pointer-events-none absolute -inset-px opacity-0 group-hover:opacity-100 transition-opacity duration-300"
               style="background: radial-gradient(400px circle at var(--mouse-x) var(--mouse-y), rgba(255,255,255,0.08), transparent 40%); z-index: 1;">
            </div>

            <!-- Background Decoration -->
            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-transparent opacity-0 group-hover:opacity-10 transition-opacity duration-500" :class="'to-' + variantToColorClass(mod.variant)"></div>
            
            <div class="flex items-center justify-between gap-3 relative z-10">
              <div class="flex items-center gap-3 min-w-0">
                <div :class="sparklineIconClasses(mod.variant)" class="group-hover:scale-110 transition-transform duration-300">
                  <component :is="mod.icon" class="w-4 h-4" />
                </div>
                <div class="min-w-0">
                  <p class="text-sm font-bold text-slate-900 dark:text-slate-200 truncate">{{ mod.name }}</p>
                  <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ mod.value }}</span>
                    <span :class="trendClasses(mod.trend)" class="!text-[10px] animate-pulse">
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
              <!-- Mini Sparkline SVG Animado -->
              <svg class="w-20 h-10 flex-shrink-0 drop-shadow-sm group-hover:drop-shadow-[0_0_8px_rgba(var(--spark-color),0.5)] transition-all duration-500" viewBox="0 0 80 32" fill="none" :style="`--spark-color: ${mod.variant === 'info' ? '6,182,212' : mod.variant === 'warning' ? '245,158,11' : '239,68,68'}`">
                <polyline
                  :points="sparklinePoints(mod.data)"
                  fill="none"
                  :stroke="sparklineColor(mod.variant)"
                  :stroke-width="2.5"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  class="trend-line-path trend-line"
                />
                <polyline
                  :points="sparklineAreaPoints(mod.data)"
                  :fill="sparklineAreaFill(mod.variant)"
                  stroke="none"
                  class="opacity-50 group-hover:opacity-80 transition-opacity"
                />
              </svg>
            </div>
          </div>
        </div>

        <!-- PMDA em Análise -->
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 overflow-hidden">
          <div class="px-4 sm:px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30">
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">PMDA em Análise</h3>
              <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Aguardando intervenção técnica</p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-500/30 animate-pulse">
              {{ pmdaEmAnalise.length }} processos
            </span>
          </div>
          <div class="divide-y divide-slate-100 dark:divide-slate-800/50">
            <div
              v-for="item in pmdaEmAnalise"
              :key="item.id"
              class="px-4 sm:px-5 py-3 hover:bg-blue-50/50 dark:hover:bg-blue-500/5 transition-all duration-300 group cursor-pointer"
            >
              <div class="flex items-center justify-between gap-3 mb-1">
                <span class="font-bold text-sm text-slate-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ item.protocolo }}</span>
                <span :class="statusBadgeClasses(item.statusType)" class="group-hover:scale-105 transition-transform">
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
        <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 overflow-hidden flex flex-col h-full">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30 backdrop-blur-sm">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Últimas Movimentações</h3>
            <div class="flex items-center gap-1.5 realtime-indicator">
              <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full absolute"></span>
              <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full relative"></span>
              <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider ml-1">Tempo real</span>
            </div>
          </div>
          <div class="p-5 overflow-y-auto custom-scrollbar flex-1 relative">
            <!-- Linha conectora de fundo contínua -->
            <div class="absolute left-[29px] top-6 bottom-6 w-0.5 bg-slate-100 dark:bg-slate-800 z-0"></div>

            <TransitionGroup name="list" tag="div" class="space-y-6 relative z-10">
              <div
                v-for="(h, index) in historico"
                :key="h.id"
                class="flex gap-4 group/item cursor-default"
                :style="{ transitionDelay: `${index * 50}ms` }"
              >
                <!-- Ícone/Dot Timeline -->
                <div class="relative flex-shrink-0 mt-1">
                  <div 
                    :class="['w-8 h-8 rounded-xl flex items-center justify-center shadow-lg ring-4 ring-white dark:ring-slate-950 transition-all duration-300 group-hover/item:scale-110 group-hover/item:rotate-3', timelineBgColor(h.type)]"
                  >
                    <component :is="timelineIcon(h.type)" class="w-4 h-4 text-white" />
                  </div>
                </div>
                
                <!-- Conteúdo Card -->
                <div class="flex-1 bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50 hover:border-slate-200 dark:hover:border-slate-600 transition-all duration-300 group-hover/item:translate-x-1 group-hover/item:shadow-md hover:bg-white dark:hover:bg-slate-800">
                  <div class="flex items-center justify-between mb-1">
                    <span class="font-bold text-sm text-slate-800 dark:text-slate-200 group-hover/item:text-blue-500 transition-colors">{{ h.municipio }}</span>
                    <span class="text-[10px] font-medium text-slate-400 dark:text-slate-500">{{ h.data }}</span>
                  </div>
                  <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed mb-2">{{ h.acao }}</p>
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 group-hover/item:border-blue-500/30 group-hover/item:text-blue-500 transition-colors">
                      {{ h.protocolo }}
                    </span>
                  </div>
                </div>
              </div>
            </TransitionGroup>
          </div>
        </div>
      </div>

      <!-- Gráfico de Linhas + Quick Stats -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8 items-start">

        <!-- Gráfico de Linhas SVG - Tendência Mensal (HUD DESIGN) -->
        <div class="lg:col-span-2 relative group isolate">
          <!-- Ambient Glow Background -->
          <div class="absolute -inset-1 bg-gradient-to-r from-cyan-500/20 via-blue-500/20 to-emerald-500/20 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition duration-1000"></div>
          
          <div class="relative rounded-2xl shadow-2xl border bg-white dark:bg-slate-900 border-slate-100 dark:border-slate-800 overflow-hidden flex flex-col">
            
            <!-- Seamless Header (Floating) -->
            <div class="absolute top-0 left-0 right-0 p-6 z-20 flex flex-col sm:flex-row sm:items-start justify-between gap-4 pointer-events-none">
              <div>
                <h3 class="font-bold text-lg text-slate-900 dark:text-white tracking-tight flex items-center gap-2 drop-shadow-md">
                  <span class="w-1 h-5 bg-gradient-to-b from-cyan-400 to-blue-500 rounded-full"></span>
                  Tendência Mensal
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 ml-3 font-medium">Fluxo de Processos</p>
              </div>
              
              <!-- Floating Legend (Pointer events enabled for this child) -->
              <div class="flex items-center gap-1 pointer-events-auto bg-white/50 dark:bg-slate-900/50 backdrop-blur-md p-1 rounded-lg border border-slate-200/50 dark:border-slate-700/50">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-cyan-500/10 border border-cyan-500/20">
                  <span class="w-2 h-2 rounded-full bg-cyan-500 shadow-[0_0_6px_currentColor]"></span>
                  <span class="text-[10px] font-bold text-cyan-700 dark:text-cyan-300">Abertos</span>
                </div>
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 border border-emerald-500/20">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_6px_currentColor]"></span>
                  <span class="text-[10px] font-bold text-emerald-700 dark:text-emerald-300">Concluídos</span>
                </div>
              </div>
            </div>

            <!-- Chart Area -->
            <div class="relative w-full bg-gradient-to-b from-slate-50/30 to-white dark:from-slate-800/10 dark:to-slate-900 pt-16 pb-4 px-4 h-full">
              <VueApexCharts type="area" height="320" width="100%" :options="trendChartOptions" :series="trendChartSeries" />
            </div>

          </div>
        </div>

        <!-- Quick Stats - Indicadores Rápidos -->
        <!-- Coluna Direita (Radar + QuickStats) -->
        <div class="space-y-6">
          <!-- Radar Chart - Avaliação 360 -->
          <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden relative group">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
              <div>
                <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Avaliação 360º</h3>
                <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Qualidade e Eficiência</p>
              </div>
            </div>
            
            <div 
              class="p-4 flex items-center justify-center relative"
              @mousemove="handleRadarMouseMove"
              @mouseleave="handleRadarMouseLeave"
            >
               <svg viewBox="0 0 300 300" class="w-full h-auto max-h-80 overflow-visible" preserveAspectRatio="xMidYMid meet">
                  <g class="text-slate-200 dark:text-slate-700">
                    <circle cx="150" cy="150" r="27.5" fill="none" stroke="currentColor" stroke-dasharray="4 4" />
                    <circle cx="150" cy="150" r="55" fill="none" stroke="currentColor" stroke-dasharray="4 4" />
                    <circle cx="150" cy="150" r="82.5" fill="none" stroke="currentColor" stroke-dasharray="4 4" />
                    <circle cx="150" cy="150" r="110" fill="none" stroke="currentColor" />
                  </g>
                  <g class="text-slate-200 dark:text-slate-700">
                     <line v-for="(point, i) in radarPoints" :key="'axis-'+i"
                           :x1="150" :y1="150" :x2="point.axis.x" :y2="point.axis.y"
                           stroke="currentColor" stroke-width="1" />
                  </g>
                  <path :d="radarPath" fill="rgba(6,182,212,0.2)" stroke="#06b6d4" stroke-width="2" class="transition-all duration-1000 ease-out" />
                  <circle v-for="(point, i) in radarPoints" :key="'pt-'+i"
                          :cx="point.point.x" :cy="point.point.y" r="4"
                          class="fill-cyan-500 stroke-white dark:stroke-slate-900 stroke-2 hover:scale-150 transition-transform cursor-pointer" />
                  <text v-for="(point, i) in radarPoints" :key="'lbl-'+i"
                        :x="point.axis.x" :y="point.axis.y"
                        :text-anchor="point.angle > 180 ? 'end' : point.angle < 180 && point.angle > 0 ? 'start' : 'middle'"
                        :dominant-baseline="point.angle > 90 && point.angle < 270 ? 'hanging' : 'auto'"
                        class="text-[10px] fill-slate-500 dark:fill-slate-400 font-medium"
                        :dx="point.angle === 0 || point.angle === 180 ? 0 : (point.angle < 180 ? 10 : -10)"
                        :dy="point.angle === 90 || point.angle === 270 ? (point.angle === 90 ? 10 : -10) : 0"
                  >
                    {{ point.label }}
                  </text>
               </svg>
            </div>
          </div>

          <!-- Quick Stats - Indicadores Rápidos -->
          <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-800/80 border-slate-100 dark:border-slate-700/50 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700">
            <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Indicadores Rápidos</h3>
            <p class="text-xs mt-0.5 text-slate-500 dark:text-slate-400">Resumo consolidado</p>
          </div>
          <div class="p-5 space-y-5" ref="quickStatsRef">
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
                <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-cyan-400 transition-all duration-1000 ease-out" :style="{ width: isQuickStatsVisible ? '16.6%' : '0%' }"></div>
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
                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all duration-1000 ease-out" :style="{ width: isQuickStatsVisible ? '94%' : '0%' }"></div>
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
                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-1000 ease-out" :style="{ width: isQuickStatsVisible ? '36%' : '0%' }"></div>
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
                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-violet-400 transition-all duration-1000 ease-out" :style="{ width: isQuickStatsVisible ? '62%' : '0%' }"></div>
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
                <div class="h-full rounded-full bg-gradient-to-r from-red-500 to-red-400 transition-all duration-1000 ease-out" :style="{ width: isQuickStatsVisible ? '78%' : '0%' }"></div>
              </div>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>
    <!-- Modal de Detalhes do Módulo -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div v-if="selectedModule" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" @click.self="closeModuleDetails">
        <!-- Backdrop Blur -->
        <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm" @click="closeModuleDetails"></div>
        
        <!-- Card do Modal -->
        <div class="relative w-full max-w-4xl bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-700/50 overflow-hidden flex flex-col max-h-[90vh]">
          
          <!-- Header -->
          <div class="px-6 py-5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between bg-slate-50 dark:bg-slate-800/50">
            <div class="flex items-center gap-4">
              <div :class="sparklineIconClasses(selectedModule.variant)" class="bg-opacity-20 p-2.5 rounded-xl">
                <component :is="selectedModule.icon" class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ selectedModule.name }}</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400">Análise detalhada - Últimos 30 dias</p>
              </div>
            </div>
            <button @click="closeModuleDetails" class="text-slate-400 hover:text-white transition-colors p-2 rounded-lg hover:bg-white/10">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Body - Gráfico Expandido -->
          <div class="p-6 flex-1 overflow-y-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
              <!-- Cards de Estatísticas Internos -->
              <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <p class="text-sm text-slate-500 mb-1">Total no Período</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ selectedModule.value }}</p>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <p class="text-sm text-slate-500 mb-1">Média Diária</p>
                <p class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ Math.round(selectedModule.value / 30) }}</p>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-xl border border-slate-100 dark:border-slate-700/50">
                <p class="text-sm text-slate-500 mb-1">Tendência</p>
                <div class="flex items-center gap-2">
                  <span class="text-2xl font-bold" :class="selectedModule.trend > 0 ? 'text-emerald-400' : 'text-rose-400'">
                    {{ selectedModule.trend > 0 ? '+' : '' }}{{ selectedModule.trend }}%
                  </span>
                  <span class="text-xs text-slate-500">vs. mês anterior</span>
                </div>
              </div>
            </div>

            <!-- Gráfico Grande SVG -->
            <div class="h-64 sm:h-80 w-full bg-slate-50 dark:bg-slate-800/30 rounded-xl border border-slate-100 dark:border-slate-700/50 p-4 relative group chart-container">
              <svg class="w-full h-full overflow-visible" viewBox="0 0 800 300" preserveAspectRatio="none">
                 <defs>
                  <linearGradient :id="'grad-' + selectedModule.name" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="sparklineColor(selectedModule.variant)" stop-opacity="0.3" />
                    <stop offset="100%" :stop-color="sparklineColor(selectedModule.variant)" stop-opacity="0.0" />
                  </linearGradient>
                </defs>

                <!-- Grid Horizontal -->
                <line v-for="i in 5" :key="i" x1="0" :y1="i * 50" x2="800" :y2="i * 50" stroke="currentColor" class="text-slate-200 dark:text-slate-700" stroke-dasharray="4 4" />

                <!-- Area Fill -->
                <path :d="detailedAreaPath" :fill="`url(#grad-${selectedModule.name})`" class="transition-all duration-1000 ease-out" />
                
                <!-- Line Path -->
                <polyline 
                  :points="detailedChartPoints" 
                  fill="none" 
                  :stroke="sparklineColor(selectedModule.variant)" 
                  stroke-width="3" 
                  stroke-linecap="round" 
                  stroke-linejoin="round"
                  class="detailed-line"
                />
              </svg>
            </div>
          </div>
          
          <!-- Footer -->
          <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
             <button @click="closeModuleDetails" class="px-4 py-2 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 rounded-lg transition-colors">
               Fechar
             </button>
             <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-lg shadow-blue-500/30">
               Ver Relatório Completo
             </button>
          </div>
        </div>
      </div>
    </Transition>
  </AuthenticatedLayout>
</template>

<script setup>
import BookOpenIcon from '@/Components/Icons/BookOpenIcon.vue';
import BuildingOfficeIcon from '@/Components/Icons/BuildingOfficeIcon.vue';
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClipboardDocumentListIcon from '@/Components/Icons/ClipboardDocumentListIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import HeartIcon from '@/Components/Icons/HeartIcon.vue';
import HomeIcon from '@/Components/Icons/HomeIcon.vue';
import PencilSquareIcon from '@/Components/Icons/PencilSquareIcon.vue';
import PageHeader from '@/Components/Organisms/PageHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';
import { computed, nextTick, onMounted, ref } from 'vue';
import VueApexCharts from 'vue3-apexcharts';


const isLoaded = ref(false);
const isQuickStatsVisible = ref(false);
const quickStatsRef = ref(null);

onMounted(() => {
  nextTick(() => {
    isLoaded.value = true;
  });
});

// ─── Dados ────────────────────────────────────────
const currentYear = ref(new Date().getFullYear());
const barPeriod = ref('6M');
const hoveredBarIndex = ref(null);

useIntersectionObserver(quickStatsRef, ([{ isIntersecting }]) => {
  if (isIntersecting) isQuickStatsVisible.value = true;
});

// Configurações Globais ApexCharts
const chartTheme = {
  mode: 'dark',
  palette: 'palette1',
};

// ─── ApexCharts Options ───────────────────────────

// 3. Tendência Mensal (Area Chart)
const trendChartOptions = computed(() => ({
  chart: {
    type: 'area',
    toolbar: { show: false },
    animations: { enabled: true, easing: 'easeinout', speed: 800 },
    background: 'transparent',
    fontFamily: 'Inter, sans-serif'
  },
  colors: ['#06b6d4', '#10b981'],
  stroke: { curve: 'smooth', width: 3 },
  dataLabels: { enabled: false },
  grid: {
    borderColor: '#334155',
    strokeDashArray: 4,
  },
  xaxis: {
    categories: trendMonths.value,
    axisBorder: { show: false },
    axisTicks: { show: false },
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
  },
  yaxis: {
    labels: { style: { colors: '#94a3b8', fontSize: '11px' } }
  },
  legend: {
    show: false
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.4,
      opacityTo: 0.05,
      stops: [0, 90, 100]
    }
  },
  tooltip: {
    theme: 'dark',
    x: { show: true },
    enabled: true,
    shared: true,
    intersect: false,
    followCursor: true,
    marker: { show: true }
  },
  crosshairs: {
    show: true,
    width: 1,
    stroke: { color: '#94a3b8', width: 1, dashArray: 4 }
  }
}));

// ─── Dados do Gráfico de Tendência (Refs) ────────────────
const trendMonths = ref(['Set', 'Out', 'Nov', 'Dez', 'Jan', 'Fev']);
const trendAbertos = ref([42, 58, 52, 68, 75, 61]);
const trendConcluidos = ref([35, 48, 55, 60, 70, 58]);

// ─── Séries do Gráfico ───────────────────────────

const trendChartSeries = computed(() => [
  { name: 'Abertos', data: trendAbertos.value },
  { name: 'Concluídos', data: trendConcluidos.value }
]);

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
const hoveredSegment = ref(null);

const donutSegments = computed(() => {
  const radius = 40;
  const circumference = 2 * Math.PI * radius;
  let accumulatedPercent = 0;

  return moduleDistribution.value.map((m, index) => {
    // Ajuste fino para não fechar o círculo completo se for 100%, 
    // mas aqui assumimos soma ~100.
    const percent = m.percent / 100;
    const dashArray = `${percent * circumference} ${circumference}`;
    const dashOffset = -accumulatedPercent * circumference;
    accumulatedPercent += percent;

    return {
      ...m,
      dashArray,
      dashOffset
    };
  });
});

// ─── Sparklines ───────────────────────────────────
const moduleSparklines = ref([
  { name: 'RAT', value: 156, trend: 18, variant: 'info', icon: DocumentTextIcon, data: [12, 19, 15, 22, 18, 25, 20] },
  { name: 'Demandas', value: 43, trend: -5, variant: 'warning', icon: ClipboardDocumentListIcon, data: [8, 12, 10, 7, 9, 6, 8] },
  { name: 'Decretações', value: 28, trend: 32, variant: 'danger', icon: ExclamationTriangleIcon, data: [3, 5, 4, 8, 6, 10, 9] },
  { name: 'PAE', value: 12, trend: 5, variant: 'success', icon: CheckCircleIcon, data: [2, 4, 3, 5, 4, 6, 5] },
  { name: 'Ajuda Humanitária', value: 204, trend: 15, variant: 'primary', icon: HeartIcon, data: [15, 25, 20, 30, 25, 35, 30] },
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
  const colors = { info: '#06b6d4', warning: '#f59e0b', danger: '#ef4444', success: '#10b981', primary: '#8b5cf6' };
  return colors[variant] || '#06b6d4';
}

function sparklineAreaFill(variant) {
  const colors = { 
    info: 'rgba(6,182,212,0.15)', 
    warning: 'rgba(245,158,11,0.15)', 
    danger: 'rgba(239,68,68,0.15)', 
    success: 'rgba(16,185,129,0.15)',
    primary: 'rgba(139,92,246,0.15)'
  };
  return colors[variant] || 'rgba(6,182,212,0.15)';
}

function sparklineIconClasses(variant) {
  const map = {
    info: 'p-2 rounded-lg bg-cyan-500/15 text-cyan-400 ring-1 ring-cyan-500/25',
    warning: 'p-2 rounded-lg bg-amber-500/15 text-amber-400 ring-1 ring-amber-500/25',
    danger: 'p-2 rounded-lg bg-red-500/15 text-red-400 ring-1 ring-red-500/25',
    success: 'p-2 rounded-lg bg-emerald-500/15 text-emerald-400 ring-1 ring-emerald-500/25',
    primary: 'p-2 rounded-lg bg-violet-500/15 text-violet-400 ring-1 ring-violet-500/25',
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

function timelineBgColor(type) {
  const map = {
    info: 'bg-gradient-to-br from-cyan-400 to-cyan-600 shadow-cyan-500/20',
    warning: 'bg-gradient-to-br from-amber-400 to-amber-600 shadow-amber-500/20',
    success: 'bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-emerald-500/20',
    danger: 'bg-gradient-to-br from-red-400 to-red-600 shadow-red-500/20',
  };
  return map[type] || map.info;
}

function timelineIcon(type) {
  const map = {
    info: DocumentTextIcon,
    warning: ExclamationTriangleIcon,
    success: CheckCircleIcon,
    danger: ExclamationTriangleIcon,
  };
  return map[type] || DocumentTextIcon;
}

// ─── Radar Chart (Hexágono) ───────────────────────
const props = defineProps({
  radarMetrics: {
    type: Array,
    default: () => [
      { label: 'Eficiência', value: 0, fullMark: 100 },
      { label: 'Tempo Resp.', value: 0, fullMark: 100 },
      { label: 'Volume', value: 0, fullMark: 100 },
      { label: 'Qualidade', value: 0, fullMark: 100 },
      { label: 'Satisfação', value: 0, fullMark: 100 },
      { label: 'Cobertura', value: 0, fullMark: 100 },
    ]
  }
});


const hoveredRadarIndex = ref(null);

function polarToCartesian(centerX, centerY, radius, angleInDegrees) {
  const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
  return {
    x: centerX + (radius * Math.cos(angleInRadians)),
    y: centerY + (radius * Math.sin(angleInRadians))
  };
}

const radarPoints = computed(() => {
  const centerX = 150;
  const centerY = 150; 
  const radius = 110; 
  const count = props.radarMetrics.length;
  
  return props.radarMetrics.map((metric, i) => {
    const angle = (360 / count) * i;
    // Ponto do eixo (100%)
    const axis = polarToCartesian(centerX, centerY, radius, angle);
    // Ponto do valor
    const valueRadius = (metric.value / metric.fullMark) * radius;
    const point = polarToCartesian(centerX, centerY, valueRadius, angle);
    
    return { ...metric, angle, axis, point };
  });
});

const radarPath = computed(() => {
  return radarPoints.value.map((p, i) => `${i === 0 ? 'M' : 'L'} ${p.point.x} ${p.point.y}`).join(' ') + ' Z';
});

// ─── Helpers de estilo ────────────────────────────
const variantBorderMap = {
  info: 'border-cyan-500/25 dark:border-cyan-500/25 border-cyan-200',
  success: 'border-emerald-500/25 dark:border-emerald-500/25 border-emerald-200',
  warning: 'border-amber-500/25 dark:border-amber-500/25 border-amber-200',
  danger: 'border-red-500/25 dark:border-red-500/25 border-red-200',
  primary: 'border-violet-500/25 dark:border-violet-500/25 border-violet-200',
};

// ─── Lógica de Movimento e Tilt 3D ────────────────
function handleTilt(e) {
  const card = e.currentTarget;
  const rect = card.getBoundingClientRect();
  const x = e.clientX - rect.left;
  const y = e.clientY - rect.top;

  const centerX = rect.width / 2;
  const centerY = rect.height / 2;

  // Calcula rotação (limitada a +/- 3 graus para sutileza)
  const rotateX = ((y - centerY) / centerY) * -3; 
  const rotateY = ((x - centerX) / centerX) * 3;

  // Define variáveis CSS para o Spotlight e Transform
  card.style.setProperty('--mouse-x', `${x}px`);
  card.style.setProperty('--mouse-y', `${y}px`);
  card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
}

function resetTilt(e) {
  const card = e.currentTarget;
  card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
  // Opcional: Manter o spotlight na última posição ou resetar
}


const variantIconMap = {
  info: 'bg-cyan-500/15 dark:bg-cyan-500/15 bg-cyan-100 text-cyan-300 dark:text-cyan-300 text-cyan-700 ring-1 ring-cyan-500/25 dark:ring-cyan-500/25 ring-cyan-300',
  success: 'bg-emerald-500/15 dark:bg-emerald-500/15 bg-emerald-100 text-emerald-300 dark:text-emerald-300 text-emerald-700 ring-1 ring-emerald-500/25 dark:ring-emerald-500/25 ring-emerald-300',
  warning: 'bg-amber-500/15 dark:bg-amber-500/15 bg-amber-100 text-amber-300 dark:text-amber-300 text-amber-700 ring-1 ring-amber-500/25 dark:ring-amber-500/25 ring-amber-300',
  danger: 'bg-red-500/15 dark:bg-red-500/15 bg-red-100 text-red-300 dark:text-red-300 text-red-700 ring-1 ring-red-500/25 dark:ring-red-500/25 ring-red-300',
  primary: 'bg-violet-500/15 dark:bg-violet-500/15 bg-violet-100 text-violet-300 dark:text-violet-300 text-violet-700 ring-1 ring-violet-500/25 dark:ring-violet-500/25 ring-violet-300',
};

function variantToColorClass(variant) {
  const map = {
    info: 'cyan-500',
    success: 'emerald-500',
    warning: 'amber-500',
    danger: 'red-500',
    primary: 'violet-500',
  };
  return map[variant] || 'cyan-500';
}

function metricCardClasses(variant) {
  return `rounded-2xl border backdrop-blur-sm px-4 py-4 md:px-5 md:py-5 transition-all duration-500 ease-out hover:shadow-[0_20px_40px_rgba(0,0,0,0.3)] dark:hover:shadow-[0_20px_40px_rgba(0,0,0,0.6)] bg-white dark:bg-slate-900/60 ${variantBorderMap[variant]} hover:border-opacity-100 dark:hover:border-opacity-100 border-opacity-40`;
}

function metricIconClasses(variant) {
  return `p-1.5 sm:p-2 md:p-3 rounded-md sm:rounded-lg transition-colors duration-500 group-hover:bg-opacity-100 ${variantIconMap[variant]}`;
}

function trendClasses(trend) {
  return [
    'inline-flex items-center gap-0.5 text-xs font-bold px-1.5 py-0.5 rounded-full transition-transform duration-300 group-hover:scale-110',
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
  transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.donut-chart:hover {
  transform: scale(1.05);
}







/* Transições para listas */
.list-move,
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

.realtime-indicator::before {
  content: '';
  position: absolute;
  width: 10px;
  height: 10px;
  background: #10b981;
  border-radius: 50%;
  animation: pulse-ring 1.5s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
}

@keyframes pulse-ring {
  0% { transform: scale(0.8); opacity: 1; }
  100% { transform: scale(3); opacity: 0; }
}

@keyframes fadeInUp {
  from { opacity: 0; transform: translateY(20px); }
  to { opacity: 1; transform: translateY(0); }
}

.animate-fade-in-up {
  animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
  opacity: 0;
}



@keyframes drawLine {
  to { stroke-dashoffset: 0; }
}

.detailed-line {
  stroke-dasharray: 2000;
  stroke-dashoffset: 2000;
  animation: drawLine 2s ease-out forwards;
}

.list-leave-active {
}

.tilt-card {
  will-change: transform;
  transform-style: preserve-3d;
  transition: transform 0.1s ease-out; /* Rápido para seguir o mouse, mas com suavidade */
}
.bar-animate {
  animation: growUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
  opacity: 0;
}

@keyframes growUp {
  from {
    transform: scaleY(0);
    opacity: 0;
  }
  to {
    transform: scaleY(1);
    opacity: 1;
  }
}

.animate-scale-in {
  animation: scaleIn 1s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes scaleIn {
  from { transform: scale(0.5); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
}

@keyframes radarSpin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.animate-radar-spin {
  animation: radarSpin 8s linear infinite;
}

.animate-pulse-slow {
  animation: pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
