<template>
  <div class="w-full overflow-x-auto py-3 mb-4">
    <div class="flex items-center min-w-max px-2">
      <template v-for="(step, index) in visibleSteps" :key="step.status">
        <div class="flex flex-col items-center">
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all',
              stepCircleClass(step.status),
            ]"
          >
            <svg v-if="isDone(step.status)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
            <span v-else>{{ index + 1 }}</span>
          </div>
          <span
            :class="[
              'mt-1 text-[10px] font-medium text-center max-w-[72px] leading-tight',
              stepLabelClass(step.status),
            ]"
          >
            {{ step.label }}
          </span>
        </div>

        <div
          v-if="index < visibleSteps.length - 1"
          :class="[
            'h-0.5 w-8 sm:w-12 flex-shrink-0 mx-1 mb-4 rounded',
            connectorClass(step.status),
          ]"
        />
      </template>
    </div>

    <div v-if="terminalBadge" class="mt-2 flex justify-center">
      <span :class="['px-3 py-1 rounded-full text-xs font-bold border', terminalBadge.classes]">
        {{ terminalBadge.label }}
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  situacao: {
    type: String,
    default: 'novo',
  },
  numProtocolo: {
    type: String,
    default: '',
  },
});

const MAIN_STEPS = [
  { status: 'novo', label: 'Novo' },
  { status: 'criacao_sdc', label: 'Criação SDC' },
  { status: 'gerenciamento', label: 'Gerenciamento' },
  { status: 'notificacao', label: 'Notificação' },
  { status: 'analise', label: 'Análise' },
  { status: 'aprovado', label: 'Aprovado' },
  { status: 'ccpae', label: 'CCPAE' },
  { status: 'ativo_3_anos', label: 'Ativo' },
];

const STATUS_ORDER = [
  'novo', 'entrada_processo', 'criacao_sdc', 'gerenciamento',
  'esperar_tratativa', 'dilacao',
  'notificacao', 'analise', 'aprovado', 'ccpae', 'ativo_3_anos',
];

const TERMINAL_CONFIGS = {
  reprovado: { label: 'Reprovado', classes: 'bg-red-500/20 text-red-300 border-red-500/30' },
  suspenso: { label: 'Suspenso', classes: 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30' },
  revogado: { label: 'Revogado', classes: 'bg-red-700/20 text-red-400 border-red-700/30' },
};

const currentIndex = computed(() => STATUS_ORDER.indexOf(props.situacao));

const visibleSteps = computed(() => MAIN_STEPS);

const terminalBadge = computed(() => TERMINAL_CONFIGS[props.situacao] ?? null);

function isDone(stepStatus) {
  const stepIdx = STATUS_ORDER.indexOf(stepStatus);
  return stepIdx !== -1 && stepIdx < currentIndex.value;
}

function isCurrent(stepStatus) {
  return props.situacao === stepStatus;
}

function stepCircleClass(stepStatus) {
  if (isCurrent(stepStatus)) {
    return 'bg-blue-500 border-blue-400 text-white shadow-lg shadow-blue-500/30';
  }
  if (isDone(stepStatus)) {
    return 'bg-green-500/20 border-green-400 text-green-300';
  }
  return 'bg-slate-700/40 border-slate-600 text-slate-500';
}

function stepLabelClass(stepStatus) {
  if (isCurrent(stepStatus)) return 'text-blue-400 font-semibold';
  if (isDone(stepStatus)) return 'text-green-400';
  return 'text-slate-500';
}

function connectorClass(stepStatus) {
  if (isDone(stepStatus)) return 'bg-green-500/40';
  return 'bg-slate-700/40';
}
</script>
