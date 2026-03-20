<template>
  <div class="rounded-xl shadow-lg border bg-white dark:bg-slate-900/60 border-slate-100 dark:border-slate-800/50 overflow-hidden flex flex-col h-full relative group">
    <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800/50 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/30 backdrop-blur-sm">
      <h3 class="font-bold text-base text-slate-900 dark:text-slate-200">Últimas Movimentações</h3>
      <div class="flex items-center gap-1.5 realtime-indicator">
        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full absolute"></span>
        <span class="w-2.5 h-2.5 bg-emerald-500 rounded-full relative"></span>
        <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider ml-1">Tempo real</span>
      </div>
    </div>
    <div class="p-5 overflow-y-auto custom-scrollbar flex-1 relative max-h-[400px]">
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
              :class="['w-8 h-8 rounded-xl flex items-center justify-center shadow-lg ring-4 ring-white dark:ring-slate-950 transition-colors duration-300 group-hover/item:scale-110 group-hover/item:rotate-3', timelineBgColor(h.type)]"
            >
              <component :is="timelineIcon(h.type)" class="w-4 h-4 text-white" />
            </div>
          </div>
          
          <!-- Conteúdo Card -->
          <div class="flex-1 bg-slate-50 dark:bg-slate-800/40 rounded-xl p-3 border border-slate-100 dark:border-slate-700/50 hover:border-slate-200 dark:hover:border-slate-600 transition-colors duration-300 group-hover/item:translate-x-1 group-hover/item:shadow-md hover:bg-white dark:hover:bg-slate-800">
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
</template>

<script setup>
import CheckCircleIcon from '@/Components/Icons/CheckCircleIcon.vue';
import ClockIcon from '@/Components/Icons/ClockIcon.vue';
import DocumentTextIcon from '@/Components/Icons/DocumentTextIcon.vue';
import ExclamationTriangleIcon from '@/Components/Icons/ExclamationTriangleIcon.vue';
import { ref } from 'vue';

// Mock Icons se não existirem (para evitar erro, usando os já importados ou genéricos)
// Ajuste: UserPlusIcon pode não existir, vou usar DocumentTextIcon como fallback se necessário ou importar corretamente.
// O UserPlusIcon não foi visto no import original do Dashboard.vue nas linhas visualizadas, mas verifiquei alguns ícones.
// Vou usar os ícones padrão importados no script setup do Dashboard.vue para garantir.

const historico = ref([
  { id: 1, type: 'approval', municipio: 'Belo Horizonte', acao: 'Plano de Trabalho Aprovado - R$ 1.5M liberação imediata via Fundo Estadual.', data: '10 min', protocolo: 'Proc. 9982/24' },
  { id: 2, type: 'alert', municipio: 'Contagem', acao: 'Alerta de Tempestade Severa emitido. Defesa Civil em prontidão nível laranja.', data: '32 min', protocolo: 'Alert. 1102' },
  { id: 3, type: 'new_process', municipio: 'Betim', acao: 'Novo processo de captação iniciado para obras de contenção de encostas.', data: '1h 15m', protocolo: 'Proc. 9981/24' },
  { id: 4, type: 'analysis', municipio: 'Juiz de Fora', acao: 'Documentação técnica em análise pela equipe de engenharia.', data: '2h 30m', protocolo: 'Proc. 9978/24' },
]);

function timelineIcon(type) {
  const map = {
    approval: CheckCircleIcon,
    alert: ExclamationTriangleIcon,
    new_process: DocumentTextIcon,
    analysis: ClockIcon,
  };
  return map[type] || DocumentTextIcon;
}

function timelineBgColor(type) {
  const map = {
    approval: 'bg-emerald-500 shadow-emerald-500/40',
    alert: 'bg-amber-500 shadow-amber-500/40',
    new_process: 'bg-blue-500 shadow-blue-500/40',
    analysis: 'bg-violet-500 shadow-violet-500/40'
  };
  return map[type] || 'bg-slate-500';
}
</script>
